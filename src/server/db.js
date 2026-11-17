'use strict';
// SQLite-Datenbank (in Electron/Node eingebaut: node:sqlite) - eine Datei im
// Benutzerordner. Schema entspricht der Laravel-Version (ohne Admin/Ausbilder-
// Tabellen), damit spaeter auch ein Import alter Daten moeglich bleibt.

const { DatabaseSync } = require('node:sqlite');
const { D } = require('./dates');
const { C } = require('./collection');

const SCHEMA = [
  // v1
  `CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vorname TEXT NOT NULL, nachname TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE, password TEXT NOT NULL,
    ausbildungsbeginn TEXT, ausbildungsende TEXT,
    abteilung TEXT, dark_mode INTEGER NOT NULL DEFAULT 0,
    signatur_pfad TEXT, remember_token TEXT,
    created_at TEXT, updated_at TEXT
  );
  CREATE TABLE wochenberichte (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    startdatum TEXT NOT NULL, enddatum TEXT NOT NULL,
    abgegeben INTEGER NOT NULL DEFAULT 0, abgegeben_am TEXT,
    auto_abgeschickt INTEGER NOT NULL DEFAULT 0,
    azubi_signiert INTEGER NOT NULL DEFAULT 0, azubi_signiert_am TEXT,
    signiert INTEGER NOT NULL DEFAULT 0, signiert_am TEXT,
    bearbeitet INTEGER NOT NULL DEFAULT 0, gesperrt INTEGER NOT NULL DEFAULT 0,
    created_at TEXT, updated_at TEXT,
    UNIQUE (azubi_id, startdatum)
  );
  CREATE TABLE wochenbericht_eintraege (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wochenbericht_id INTEGER NOT NULL REFERENCES wochenberichte(id) ON DELETE CASCADE,
    kategorie TEXT NOT NULL CHECK (kategorie IN ('betrieb','unterweisung','berufsschule')),
    beschreibung TEXT NOT NULL, stunden REAL NOT NULL DEFAULT 0,
    stunden_abziehen_bei TEXT, created_at TEXT, updated_at TEXT
  );
  CREATE TABLE wochenbericht_bearbeitungen (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wochenbericht_id INTEGER NOT NULL REFERENCES wochenberichte(id) ON DELETE CASCADE,
    autor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kommentar TEXT NOT NULL, created_at TEXT, updated_at TEXT
  );
  CREATE TABLE wochenbericht_kommentare (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wochenbericht_id INTEGER NOT NULL REFERENCES wochenberichte(id) ON DELETE CASCADE,
    autor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    inhalt TEXT NOT NULL, created_at TEXT, updated_at TEXT
  );
  CREATE TABLE aufgaben (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    titel TEXT NOT NULL, beschreibung TEXT,
    kategorie TEXT NOT NULL CHECK (kategorie IN ('betrieb','berufsschule','unterweisung')),
    stunden_abziehen_bei TEXT, stunden REAL NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'geplant' CHECK (status IN ('geplant','in_bearbeitung','fertig')),
    archiviert INTEGER NOT NULL DEFAULT 0, archiviert_am TEXT,
    in_wochenbericht INTEGER NOT NULL DEFAULT 0,
    created_at TEXT, updated_at TEXT
  );
  CREATE TABLE stunden_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    max_stunden REAL NOT NULL DEFAULT 42, betrieb_stunden REAL NOT NULL DEFAULT 32,
    berufsschule_stunden REAL NOT NULL DEFAULT 10, created_at TEXT, updated_at TEXT
  );
  CREATE TABLE kalender_eintraege (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ausbildungsjahr INTEGER NOT NULL DEFAULT 1,
    fach TEXT NOT NULL,
    typ TEXT NOT NULL CHECK (typ IN ('test','klassenarbeit','hausaufgabe','projekt','sonstiges')),
    datum TEXT NOT NULL, uhrzeit TEXT, thema TEXT, lernnotizen TEXT, note REAL,
    created_at TEXT, updated_at TEXT
  );
  CREATE TABLE faecher (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ausbildungsjahr INTEGER NOT NULL DEFAULT 1,
    name TEXT NOT NULL, beschreibung TEXT, created_at TEXT, updated_at TEXT,
    UNIQUE (azubi_id, ausbildungsjahr, name)
  );
  CREATE TABLE zeitraeume (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    azubi_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    von_datum TEXT NOT NULL, bis_datum TEXT NOT NULL, bezeichnung TEXT NOT NULL,
    created_at TEXT, updated_at TEXT
  );
  CREATE INDEX zeitraeume_von_bis ON zeitraeume (von_datum, bis_datum);`,
];

// Spalten, die beim Lesen in Datumsobjekte / Booleans / Zahlen gewandelt werden.
const DATE_COLS = new Set(['ausbildungsbeginn', 'ausbildungsende', 'startdatum', 'enddatum', 'datum', 'von_datum', 'bis_datum',
  'created_at', 'updated_at', 'abgegeben_am', 'azubi_signiert_am', 'signiert_am', 'archiviert_am']);
// Reine Datumsspalten werden immer als 'YYYY-MM-DD' gespeichert (Vergleiche per String).
const DATE_ONLY = new Set(['ausbildungsbeginn', 'ausbildungsende', 'startdatum', 'enddatum', 'datum', 'von_datum', 'bis_datum']);
const colVal = (k, v) => (DATE_ONLY.has(k) && v instanceof D ? v.toDateString() : v);
const BOOL_COLS = new Set(['abgegeben', 'auto_abgeschickt', 'azubi_signiert', 'signiert', 'bearbeitet', 'gesperrt', 'archiviert',
  'in_wochenbericht', 'dark_mode']);

function hydrate(row) {
  if (!row) return null;
  const o = {};
  for (const [k, v] of Object.entries(row)) {
    if (DATE_COLS.has(k)) o[k] = v == null ? null : D.parse(v);
    else if (BOOL_COLS.has(k)) o[k] = !!v;
    else o[k] = v;
  }
  return o;
}

/** Wandelt JS-Werte in SQLite-taugliche Werte. */
function dbVal(v) {
  if (v === undefined) return null;
  if (v instanceof D) return v.toDateTimeString();
  if (typeof v === 'boolean') return v ? 1 : 0;
  return v;
}

class Db {
  constructor(file) {
    this.raw = new DatabaseSync(file);
    this.raw.exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 3000;');
    this.migrate();
  }

  migrate() {
    const v = this.raw.prepare('PRAGMA user_version').get().user_version;
    for (let i = v; i < SCHEMA.length; i++) {
      this.raw.exec('BEGIN');
      try {
        this.raw.exec(SCHEMA[i]);
        this.raw.exec(`PRAGMA user_version = ${i + 1}`);
        this.raw.exec('COMMIT');
      } catch (e) {
        this.raw.exec('ROLLBACK');
        throw e;
      }
    }
  }

  all(sql, ...p) { return C.from(this.raw.prepare(sql).all(...p.map(dbVal)).map(hydrate)); }
  get(sql, ...p) { return hydrate(this.raw.prepare(sql).get(...p.map(dbVal))); }
  value(sql, ...p) { const r = this.raw.prepare(sql).get(...p.map(dbVal)); return r ? Object.values(r)[0] : null; }
  run(sql, ...p) { return this.raw.prepare(sql).run(...p.map(dbVal)); }

  /** INSERT mit automatischen Zeitstempeln, liefert neue ID. */
  insert(table, data) {
    const jetzt = D.now();
    const d = { ...data, created_at: jetzt, updated_at: jetzt };
    const keys = Object.keys(d);
    const r = this.run(`INSERT INTO ${table} (${keys.join(',')}) VALUES (${keys.map(() => '?').join(',')})`, ...keys.map(k => colVal(k, d[k])));
    return Number(r.lastInsertRowid);
  }

  /** UPDATE ... WHERE id = ? mit updated_at. */
  update(table, id, data) {
    const d = { ...data, updated_at: D.now() };
    const keys = Object.keys(d);
    this.run(`UPDATE ${table} SET ${keys.map(k => `${k} = ?`).join(', ')} WHERE id = ?`, ...keys.map(k => colVal(k, d[k])), id);
  }

  transaction(fn) {
    this.raw.exec('BEGIN');
    try { const r = fn(); this.raw.exec('COMMIT'); return r; } catch (e) { this.raw.exec('ROLLBACK'); throw e; }
  }

  close() { this.raw.close(); }
}

module.exports = { Db, hydrate };
