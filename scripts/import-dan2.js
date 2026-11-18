'use strict';
// Einmaliger Import der Daten EINES Azubi-Kontos aus der alten DAN2-Datenbank
// (Laravel/MySQL in XAMPP) in die lokale DAN-App.
//
//   node scripts/import-dan2.js --azubi 1 [--ziel <Datenordner>] [--dan2 C:\xampp\htdocs\DAN2] [--force]
//
// Ziel ist standardmaessig %APPDATA%\DAN\daten (der Ordner der installierten App).
// DAN muss dabei geschlossen sein. Ohne --force bricht der Import ab, wenn dort
// schon ein Konto existiert.

const fs = require('node:fs');
const path = require('node:path');
const mysql = require('mysql2/promise');
const { Db } = require('../src/server/db');
const { D } = require('../src/server/dates');

const args = Object.fromEntries(process.argv.slice(2).reduce((acc, a, i, arr) => {
  if (a.startsWith('--')) acc.push([a.slice(2), arr[i + 1] && !arr[i + 1].startsWith('--') ? arr[i + 1] : true]);
  return acc;
}, []));

const AZUBI = Number(args.azubi);
const ZIEL = args.ziel || path.join(process.env.APPDATA, 'DAN', 'daten');
const DAN2 = args.dan2 || 'C:\\xampp\\htdocs\\DAN2';

// Laravel lief mit APP_TIMEZONE=UTC: Zeitstempel sind UTC -> in lokale Zeit wandeln.
const zeit = (v) => (v == null ? null : D.parse(new Date(`${fmtMysql(v)}Z`)).toDateTimeString());
const datum = (v) => (v == null ? null : fmtMysql(v).slice(0, 10));
function fmtMysql(v) {
  if (v instanceof Date) {
    const p = (n) => String(n).padStart(2, '0');
    return `${v.getFullYear()}-${p(v.getMonth() + 1)}-${p(v.getDate())}T${p(v.getHours())}:${p(v.getMinutes())}:${p(v.getSeconds())}`;
  }
  return String(v).replace(' ', 'T');
}

async function main() {
  if (!AZUBI) throw new Error('Bitte --azubi <ID> angeben.');
  fs.mkdirSync(path.join(ZIEL, 'signaturen'), { recursive: true });

  const my = await mysql.createConnection({ host: '127.0.0.1', port: 3306, user: 'root', database: 'dan2', dateStrings: true });
  const q = async (sql, ...p) => (await my.query(sql, p))[0];

  const [u] = await q('SELECT * FROM users WHERE id = ?', AZUBI);
  if (!u) throw new Error(`Konto ${AZUBI} nicht gefunden.`);
  if (u.rolle !== 'azubi') throw new Error(`Konto ${AZUBI} ist kein Azubi-Konto.`);
  let abteilung = u.abteilung;
  if (!abteilung && u.klasse_id) {
    const [k] = await q('SELECT a.name FROM klassen k JOIN abteilungen a ON a.id = k.abteilung_id WHERE k.id = ?', u.klasse_id);
    abteilung = k?.name ?? null;
  }

  const db = new Db(path.join(ZIEL, 'dan.sqlite'));
  if (db.get('SELECT id FROM users LIMIT 1')) {
    if (!args.force) throw new Error(`In ${ZIEL} gibt es schon ein Konto. Abbruch (mit --force ueberschreiben).`);
    for (const t of ['wochenbericht_kommentare', 'wochenbericht_bearbeitungen', 'wochenbericht_eintraege', 'wochenberichte', 'aufgaben', 'stunden_config', 'kalender_eintraege', 'faecher', 'zeitraeume', 'users']) db.run(`DELETE FROM ${t}`);
  }

  // Signaturbild kopieren
  let signatur = null;
  if (u.signatur_pfad) {
    const quelle = path.join(DAN2, 'storage', 'app', 'public', u.signatur_pfad);
    if (fs.existsSync(quelle)) {
      signatur = `signaturen/${path.basename(u.signatur_pfad)}`;
      fs.copyFileSync(quelle, path.join(ZIEL, signatur));
    } else console.warn(`Signaturbild fehlt: ${quelle}`);
  }

  const n = {};
  const ins = (tabelle, zeile) => {
    const keys = Object.keys(zeile);
    db.run(`INSERT INTO ${tabelle} (${keys.join(',')}) VALUES (${keys.map(() => '?').join(',')})`, ...keys.map(k => zeile[k]));
    n[tabelle] = (n[tabelle] || 0) + 1;
  };
  const b = (v) => (v ? 1 : 0);

  const berichte = await q('SELECT * FROM wochenberichte WHERE azubi_id = ?', AZUBI);
  const ids = berichte.map(r => r.id);
  const inIds = async (tabelle) => (ids.length ? q(`SELECT * FROM ${tabelle} WHERE wochenbericht_id IN (?)`, ids) : []);
  const eintraege = await inIds('wochenbericht_eintraege');
  const bearbeitungen = await inIds('wochenbericht_bearbeitungen');
  const kommentare = await inIds('wochenbericht_kommentare');
  const aufgaben = await q('SELECT * FROM aufgaben WHERE azubi_id = ?', AZUBI);
  const config = await q('SELECT * FROM stunden_config WHERE azubi_id = ?', AZUBI);
  const kalender = await q('SELECT * FROM kalender_eintraege WHERE azubi_id = ?', AZUBI);
  const faecher = await q('SELECT * FROM faecher WHERE azubi_id = ?', AZUBI);
  const zeitraeume = await q('SELECT * FROM zeitraeume WHERE azubi_id = ?', AZUBI);
  await my.end();

  db.transaction(() => {
    ins('users', {
      id: 1, vorname: u.vorname, nachname: u.nachname, email: String(u.email).toLowerCase(), password: u.password,
      ausbildungsbeginn: datum(u.ausbildungsbeginn), ausbildungsende: datum(u.ausbildungsende), abteilung,
      dark_mode: b(u.dark_mode), signatur_pfad: signatur, created_at: zeit(u.created_at), updated_at: zeit(u.updated_at),
    });
    for (const r of berichte) {
      ins('wochenberichte', {
        id: r.id, azubi_id: 1, startdatum: datum(r.startdatum), enddatum: datum(r.enddatum),
        abgegeben: b(r.abgegeben), abgegeben_am: zeit(r.abgegeben_am), auto_abgeschickt: b(r.auto_abgeschickt),
        azubi_signiert: b(r.azubi_signiert), azubi_signiert_am: zeit(r.azubi_signiert_am),
        signiert: b(r.signiert), signiert_am: zeit(r.signiert_am), bearbeitet: b(r.bearbeitet), gesperrt: b(r.gesperrt),
        created_at: zeit(r.created_at), updated_at: zeit(r.updated_at),
      });
    }
    for (const r of eintraege) {
      ins('wochenbericht_eintraege', {
        id: r.id, wochenbericht_id: r.wochenbericht_id, kategorie: r.kategorie, beschreibung: r.beschreibung,
        stunden: Number(r.stunden), stunden_abziehen_bei: r.stunden_abziehen_bei ?? null,
        created_at: zeit(r.created_at), updated_at: zeit(r.updated_at),
      });
    }
    for (const r of bearbeitungen) {
      ins('wochenbericht_bearbeitungen', { id: r.id, wochenbericht_id: r.wochenbericht_id, autor_id: 1, kommentar: r.kommentar, created_at: zeit(r.created_at), updated_at: zeit(r.updated_at) });
    }
    for (const r of kommentare) {
      ins('wochenbericht_kommentare', { id: r.id, wochenbericht_id: r.wochenbericht_id, autor_id: 1, inhalt: r.inhalt, created_at: zeit(r.created_at), updated_at: zeit(r.updated_at) });
    }
    for (const r of aufgaben) {
      ins('aufgaben', {
        id: r.id, azubi_id: 1, titel: r.titel, beschreibung: r.beschreibung, kategorie: r.kategorie,
        stunden_abziehen_bei: r.stunden_abziehen_bei, stunden: Number(r.stunden), status: r.status,
        archiviert: b(r.archiviert), archiviert_am: zeit(r.archiviert_am), in_wochenbericht: b(r.in_wochenbericht),
        created_at: zeit(r.created_at), updated_at: zeit(r.updated_at),
      });
    }
    for (const r of config) {
      ins('stunden_config', {
        azubi_id: 1, max_stunden: Number(r.max_stunden), betrieb_stunden: Number(r.betrieb_stunden),
        berufsschule_stunden: Number(r.berufsschule_stunden), created_at: zeit(r.created_at), updated_at: zeit(r.updated_at),
      });
    }
    for (const r of kalender) {
      ins('kalender_eintraege', {
        id: r.id, azubi_id: 1, ausbildungsjahr: r.ausbildungsjahr ?? 1, fach: r.fach, typ: r.typ, datum: datum(r.datum),
        uhrzeit: r.uhrzeit ? String(r.uhrzeit).slice(0, 5) : null, thema: r.thema, lernnotizen: r.lernnotizen,
        note: r.note == null ? null : Number(r.note), created_at: zeit(r.created_at), updated_at: zeit(r.updated_at),
      });
    }
    for (const r of faecher) {
      ins('faecher', { id: r.id, azubi_id: 1, ausbildungsjahr: r.ausbildungsjahr ?? 1, name: r.name, beschreibung: r.beschreibung ?? null, created_at: zeit(r.created_at), updated_at: zeit(r.updated_at) });
    }
    for (const r of zeitraeume) {
      ins('zeitraeume', { id: r.id, azubi_id: 1, von_datum: datum(r.von_datum), bis_datum: datum(r.bis_datum), bezeichnung: r.bezeichnung, created_at: zeit(r.created_at), updated_at: zeit(r.updated_at) });
    }
  });
  db.close();

  console.log(`Import fertig nach ${ZIEL}`);
  console.log(`Konto: ${u.vorname} ${u.nachname} <${u.email}>`);
  console.table(n);
}

main().catch((e) => { console.error('FEHLER:', e.message); process.exit(1); });
