'use strict';
const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { Db } = require('../src/server/db');
const { D } = require('../src/server/dates');
const logic = require('../src/server/logic');

function frischeDb() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-logic-'));
  const db = new Db(path.join(dir, 'dan.sqlite'));
  const id = db.insert('users', { vorname: 'A', nachname: 'B', email: 'a@b.de', password: 'x', ausbildungsbeginn: D.now().subDays(100).toDateString() });
  const user = db.get('SELECT * FROM users WHERE id = ?', id);
  return { db, user, done: () => { db.close(); fs.rmSync(dir, { recursive: true, force: true }); } };
}

test('Vorwoche wird nur mit Aufgaben abgeschlossen, die vor dieser Woche existierten', () => {
  const { db, user, done } = frischeDb();
  const alt = db.insert('aufgaben', { azubi_id: user.id, titel: 'Alt', kategorie: 'betrieb', stunden: 8, status: 'fertig' });
  db.run('UPDATE aufgaben SET created_at = ? WHERE id = ?', D.now().subWeek().startOfWeek().addDays(1), alt);
  db.insert('aufgaben', { azubi_id: user.id, titel: 'Neu', kategorie: 'betrieb', stunden: 4, status: 'in_bearbeitung' });

  logic.schliesseVorwocheAbFallsNoetig(db, user);

  const vor = db.get('SELECT * FROM wochenberichte WHERE azubi_id = ? AND startdatum = ?', user.id, logic.wocheStart(D.now().subWeek()));
  assert.ok(vor.abgegeben && vor.auto_abgeschickt);
  const titel = db.all('SELECT beschreibung FROM wochenbericht_eintraege WHERE wochenbericht_id = ?', vor.id).map(e => e.beschreibung);
  assert.deepStrictEqual(Array.from(titel), ['Alt']);
  assert.strictEqual(db.get("SELECT archiviert FROM aufgaben WHERE titel = 'Alt'").archiviert, true);
  assert.strictEqual(db.get("SELECT in_wochenbericht FROM aufgaben WHERE titel = 'Neu'").in_wochenbericht, false);

  // Zweiter Aufruf aendert nichts mehr
  logic.schliesseVorwocheAbFallsNoetig(db, user);
  assert.strictEqual(db.value('SELECT COUNT(*) FROM wochenbericht_eintraege'), 1);
  done();
});

test('Nur neue Aufgaben: Vorwoche bleibt unberuehrt', () => {
  const { db, user, done } = frischeDb();
  db.insert('aufgaben', { azubi_id: user.id, titel: 'Neu', kategorie: 'betrieb', stunden: 4, status: 'fertig' });
  logic.schliesseVorwocheAbFallsNoetig(db, user);
  assert.strictEqual(db.value('SELECT COUNT(*) FROM wochenberichte'), 0);
  done();
});

test('Zeitraum: Teilwoche kuerzt Sollstunden und fuegt Hinweis ein', () => {
  const { db, user, done } = frischeDb();
  const mo = D.parse('2026-11-16');
  db.insert('zeitraeume', { azubi_id: user.id, von_datum: '2026-11-16', bis_datum: '2026-11-17', bezeichnung: 'Krank' });
  const bericht = { azubi_id: user.id, startdatum: mo, enddatum: mo.addDays(4) };
  const eintraege = { betrieb: [], unterweisung: [], berufsschule: [] };
  const stunden = { betrieb: 40, unterweisung: 0, berufsschule: 10 };
  logic.ueberschreibeFuerWoche(db, user.id, bericht.startdatum, bericht.enddatum, eintraege, stunden);
  assert.strictEqual(eintraege.betrieb[0].beschreibung, 'Mo–Di: Krank');
  assert.strictEqual(stunden.betrieb, 24);
  assert.strictEqual(stunden.berufsschule, 6);
  done();
});

test('Datumshilfen', () => {
  assert.strictEqual(D.parse('2026-11-19').startOfWeek().toDateString(), '2026-11-16');
  assert.strictEqual(D.parse('2027-01-01').format('o-W'), '2026-53');
  assert.strictEqual(Math.floor(D.parse('2024-09-01').diffInMonths(D.parse('2025-08-31')) / 12), 0);
  assert.strictEqual(Math.floor(D.parse('2024-09-01').diffInMonths(D.parse('2025-09-01')) / 12), 1);
});
