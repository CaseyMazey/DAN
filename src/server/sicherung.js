'use strict';
// Datensicherung: eine einzige Datei (.dan) = SQLite-Datenbank plus die
// Signaturbilder in einer Zusatztabelle. Damit laesst sich DAN komplett
// sichern, auf einen anderen Rechner umziehen oder wiederherstellen.

const fs = require('node:fs');
const path = require('node:path');
const { DatabaseSync } = require('node:sqlite');
const { SCHEMA_VERSION } = require('./db');

const DATEI_TABELLE = '_dan_dateien';

/** Schreibt eine vollstaendige Sicherung nach zielDatei. */
function erstelleSicherung(db, dataDir, zielDatei) {
  fs.rmSync(zielDatei, { force: true });
  db.raw.exec(`VACUUM INTO '${zielDatei.replace(/'/g, "''")}'`);
  const s = new DatabaseSync(zielDatei);
  try {
    s.exec(`CREATE TABLE ${DATEI_TABELLE} (pfad TEXT PRIMARY KEY, daten BLOB NOT NULL)`);
    const ins = s.prepare(`INSERT INTO ${DATEI_TABELLE} (pfad, daten) VALUES (?, ?)`);
    const sigDir = path.join(dataDir, 'signaturen');
    if (fs.existsSync(sigDir)) {
      for (const f of fs.readdirSync(sigDir)) ins.run(`signaturen/${f}`, fs.readFileSync(path.join(sigDir, f)));
    }
  } finally {
    s.close();
  }
}

/** Prueft eine Sicherungsdatei; wirft mit verstaendlicher Meldung, wenn sie unbrauchbar ist. */
function pruefeSicherung(datei) {
  const kopf = Buffer.alloc(16);
  const fd = fs.openSync(datei, 'r');
  fs.readSync(fd, kopf, 0, 16, 0);
  fs.closeSync(fd);
  if (kopf.toString('latin1') !== 'SQLite format 3\0') throw new Error('Das ist keine DAN-Sicherungsdatei.');
  const s = new DatabaseSync(datei, { readOnly: true });
  try {
    if (s.prepare('PRAGMA integrity_check').get().integrity_check !== 'ok') throw new Error('Die Sicherungsdatei ist beschädigt.');
    const version = s.prepare('PRAGMA user_version').get().user_version;
    if (version > SCHEMA_VERSION) throw new Error('Die Sicherung stammt aus einer neueren DAN-Version. Bitte DAN aktualisieren.');
    const tabellen = new Set(s.prepare("SELECT name FROM sqlite_master WHERE type='table'").all().map(r => r.name));
    for (const t of ['users', 'wochenberichte', 'aufgaben']) if (!tabellen.has(t)) throw new Error('Das ist keine DAN-Sicherungsdatei.');
    if (!s.prepare('SELECT id FROM users LIMIT 1').get()) throw new Error('Die Sicherung enthält kein Konto.');
    return {
      version,
      berichte: s.prepare('SELECT COUNT(*) n FROM wochenberichte').get().n,
      aufgaben: s.prepare('SELECT COUNT(*) n FROM aufgaben').get().n,
    };
  } finally {
    s.close();
  }
}

/**
 * Ersetzt die aktuellen Daten durch die Sicherung. Der bisherige Stand wird
 * vorher unter daten/vorher/ abgelegt (nichts geht verloren).
 */
function stelleWiederHer(db, dataDir, quellDatei) {
  const info = pruefeSicherung(quellDatei);
  const stempel = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
  const vorher = path.join(dataDir, 'vorher', stempel);
  fs.mkdirSync(vorher, { recursive: true });

  // Bisherigen Stand sichern (inkl. Signaturen)
  erstelleSicherung(db, dataDir, path.join(vorher, 'dan-vorher.dan'));

  // Neue Datenbank vorbereiten: Dateien auspacken, Zusatztabelle entfernen
  const neu = path.join(dataDir, `dan-neu-${stempel}.sqlite`);
  fs.copyFileSync(quellDatei, neu);
  const s = new DatabaseSync(neu);
  let dateien = [];
  try {
    const hat = s.prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = ?").get(DATEI_TABELLE);
    if (hat) {
      dateien = s.prepare(`SELECT pfad, daten FROM ${DATEI_TABELLE}`).all();
      s.exec(`DROP TABLE ${DATEI_TABELLE}`);
    }
  } finally {
    s.close();
  }

  // Austauschen
  const ziel = path.join(dataDir, 'dan.sqlite');
  db.close();
  try {
    for (const endung of ['', '-wal', '-shm']) fs.rmSync(ziel + endung, { force: true });
    fs.renameSync(neu, ziel);
    const sigDir = path.join(dataDir, 'signaturen');
    fs.rmSync(sigDir, { recursive: true, force: true });
    fs.mkdirSync(sigDir, { recursive: true });
    for (const { pfad, daten } of dateien) {
      const p = path.join(dataDir, pfad);
      if (path.dirname(p) === sigDir) fs.writeFileSync(p, daten);
    }
  } finally {
    db.oeffne(); // auch im Fehlerfall wieder eine Datenbank oeffnen (migriert bei Bedarf)
  }
  return info;
}

module.exports = { erstelleSicherung, pruefeSicherung, stelleWiederHer };
