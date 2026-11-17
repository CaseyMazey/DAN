'use strict';
// Ende-zu-Ende-Durchlauf: Konto anlegen, alle Seiten aufrufen, typische
// Aktionen ausfuehren und PDFs erzeugen. Startet den Server auf einem freien
// Port mit einem frischen Datenordner.

const test = require('node:test');
const assert = require('node:assert');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { createApp } = require('../src/server/app');
const { D } = require('../src/server/dates');

let server, base, dataDir, app;
const cookies = {};

async function req(method, url, form, { json = false, follow = false } = {}) {
  const headers = { Cookie: Object.entries(cookies).map(([k, v]) => `${k}=${v}`).join('; '), Referer: base + '/dashboard' };
  let body;
  if (form) {
    headers['Content-Type'] = 'application/x-www-form-urlencoded';
    body = new URLSearchParams(form).toString();
  }
  if (json) headers.Accept = 'application/json';
  const res = await fetch(base + url, { method, headers, body, redirect: 'manual' });
  for (const c of res.headers.getSetCookie()) {
    const [kv] = c.split(';');
    const [k, v] = kv.split('=');
    if (/Max-Age=0/.test(c)) delete cookies[k]; else cookies[k] = v;
  }
  if (follow && res.status === 302) return req('GET', new URL(res.headers.get('location'), base).pathname + new URL(res.headers.get('location'), base).search);
  return res;
}
const html = async (url) => {
  const r = await req('GET', url);
  const t = await r.text();
  assert.strictEqual(r.status, 200, `${url} -> ${r.status}\n${t.slice(0, 2000)}`);
  return t;
};

test.before(async () => {
  dataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-test-'));
  process.env.DAN_DEV = '1';
  app = createApp({ dataDir });
  await new Promise(r => { server = app.listen(0, '127.0.0.1', r); });
  base = `http://127.0.0.1:${server.address().port}`;
});
test.after(() => { server.close(); app.locals.db.close(); fs.rmSync(dataDir, { recursive: true, force: true }); });

test('Ersteinrichtung, Login und alle Seiten', async () => {
  let r = await req('GET', '/login');
  assert.strictEqual(r.headers.get('location'), '/einrichten');

  r = await req('POST', '/einrichten', { vorname: 'Max', nachname: 'Muster', email: 'Max@Example.de', password: 'kurz', password_confirmation: 'kurz', ausbildungsbeginn: '2025-09-01' });
  assert.strictEqual(r.status, 302);
  assert.match(await html('/einrichten'), /mindestens 8 Zeichen/);

  const beginn = D.now().subDays(400).toDateString();
  r = await req('POST', '/einrichten', { vorname: 'Max', nachname: 'Muster', email: 'Max@Example.de', password: 'geheim123', password_confirmation: 'geheim123', ausbildungsbeginn: beginn, ausbildungsende: D.now().addDays(700).toDateString() });
  assert.strictEqual(r.headers.get('location'), '/dashboard');
  assert.match(await html('/dashboard'), /Hallo, Max/);

  // Einrichten ist danach gesperrt
  r = await req('GET', '/einrichten');
  assert.strictEqual(r.status, 302);

  // Logout + Login (E-Mail case-insensitiv)
  await req('POST', '/logout', {});
  r = await req('GET', '/dashboard');
  assert.strictEqual(r.headers.get('location'), '/login');
  r = await req('POST', '/login', { email: 'max@example.de', password: 'falsch' });
  assert.match(await html('/login'), /E-Mail oder Passwort ist falsch/);
  r = await req('POST', '/login', { email: 'MAX@example.de', password: 'geheim123', remember: 'on' });
  assert.strictEqual(r.headers.get('location'), '/dashboard');

  // Aufgaben
  assert.match(await html('/aufgaben'), /Keine Aufgaben/);
  r = await req('POST', '/aufgaben', { titel: 'Server einrichten', beschreibung: 'Ubuntu + Nginx', kategorie: 'betrieb', stunden: '7.5' });
  assert.strictEqual(r.status, 302);
  r = await req('POST', '/aufgaben', { titel: 'Krumm', kategorie: 'betrieb', stunden: '3.37' });
  assert.match(await html('/aufgaben'), /Schritten von 0,25/);
  await req('POST', '/aufgaben', { titel: 'Erste Hilfe', kategorie: 'unterweisung', stunden: '2', stunden_abziehen_bei: 'betrieb' });
  await req('POST', '/aufgaben', { titel: 'SQL Joins', kategorie: 'berufsschule', stunden: '4' });
  const seite = await html('/aufgaben');
  const ids = [...seite.matchAll(/data-id="(\d+)" data-titel/g)].map(m => m[1]);
  assert.strictEqual(ids.length, 3);
  for (const id of ids) {
    r = await req('POST', `/aufgaben/${id}/status`, { _method: 'PATCH', status: 'in_bearbeitung' }, { json: true });
    assert.deepStrictEqual(await r.json(), { success: true });
  }
  await req('POST', `/aufgaben/${ids[0]}`, { _method: 'PATCH', titel: 'Server einrichten (neu)', kategorie: 'betrieb', stunden: '8' });
  assert.match(await html('/aufgaben'), /Server einrichten \(neu\)/);
  await req('POST', '/aufgaben/config', { _method: 'PATCH', max_stunden: '40', betrieb_stunden: '30', berufsschule_stunden: '10' });

  // Wochenberichte (Luecken-Scan legt Berichte seit Ausbildungsbeginn an)
  let wb = await html('/wochenbericht');
  assert.match(wb, /Server einrichten \(neu\)/);
  const anzahl = (wb.match(/data-id="\d+"/g) || []).length;
  assert.ok(anzahl >= 50, `erwartet >= 50 Berichte, gefunden ${anzahl}`);

  r = await req('GET', '/wochenbericht/aktuell');
  const aktuell = await r.text();
  assert.strictEqual(r.status, 200, aktuell.slice(0, 1500));
  const aktuellId = aktuell.match(/\/wochenbericht\/(\d+)\/abgeben/)[1];

  r = await req('GET', `/wochenbericht/${aktuellId}/pdf`);
  assert.strictEqual(r.headers.get('content-type'), 'application/pdf');
  const pdf1 = Buffer.from(await r.arrayBuffer());
  assert.ok(pdf1.subarray(0, 4).toString() === '%PDF' && pdf1.length > 10000);

  await req('POST', `/wochenbericht/${aktuellId}/abgeben`, {});
  await html(`/wochenbericht/${aktuellId}`);
  r = await req('POST', `/wochenbericht/${aktuellId}/speichern`, { kategorie: 'betrieb', beschreibung: 'Nachtrag', stunden: '1' });
  assert.match(await html(`/wochenbericht/${aktuellId}`), /Kommentar ist erforderlich/);
  await req('POST', `/wochenbericht/${aktuellId}/speichern`, { kategorie: 'betrieb', beschreibung: 'Nachtrag', stunden: '1', bearbeitungs_kommentar: 'vergessen' });
  const detail = await html(`/wochenbericht/${aktuellId}`);
  assert.match(detail, /Nachtrag/);
  assert.match(detail, /vergessen/);
  await req('POST', `/wochenbericht/${aktuellId}/signieren`, {});
  wb = await html(`/wochenbericht?vorschau=${aktuellId}`);
  assert.match(wb, /Signiert/);

  // Signatur (1x1 PNG) und PDF mit Signatur
  const png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
  r = await req('POST', '/profile/signatur', { _method: 'PATCH', signatur_canvas: png });
  assert.strictEqual(r.status, 302);
  r = await req('GET', '/profile/signatur/bild');
  assert.strictEqual(r.status, 200);
  r = await req('GET', `/wochenbericht/${aktuellId}/pdf`);
  assert.strictEqual(r.status, 200);
  if (process.env.DAN_PDF_OUT) fs.writeFileSync(process.env.DAN_PDF_OUT, Buffer.from(await r.arrayBuffer()));

  // Sammel-PDF ueber mehrere Berichte
  const alleIds = [...wb.matchAll(/data-id="(\d+)"/g)].map(m => m[1]).slice(0, 5);
  r = await req('POST', '/wochenbericht/sammel-pdf', { ids: [aktuellId, ...alleIds].join(',') });
  assert.strictEqual(r.headers.get('content-type'), 'application/pdf', await r.text().catch(() => ''));

  // Zeitraeume
  assert.match(await html('/wochenbericht/zeitraeume'), /Zeitr/);
  const mo = D.now().startOfWeek();
  await req('POST', '/wochenbericht/zeitraeume', { von_datum: mo.toDateString(), bis_datum: mo.addDays(1).toDateString(), bezeichnung: 'Krank' });
  await req('POST', '/wochenbericht/zeitraeume', { von_datum: mo.toDateString(), bis_datum: mo.addDays(2).toDateString(), bezeichnung: 'Doppelt' });
  const zr = await html('/wochenbericht/zeitraeume');
  assert.match(zr, /Krank/);
  assert.match(zr, /überschneidet sich/);
  assert.match(await html('/wochenbericht'), /Mo–Di: Krank/);

  // Kalender / Noten
  await html('/kalender');
  r = await req('POST', '/kalender/fach', { name: 'Mathe', ausbildungsjahr: '2' }, { json: true });
  assert.strictEqual((await r.json()).name, 'Mathe');
  await req('POST', '/kalender', { fach: 'Mathe', typ: 'klassenarbeit', datum: D.now().subDays(3).toDateString(), note: '2', ausbildungsjahr: '2' });
  await req('POST', '/kalender', { fach: 'Mathe', typ: 'test', datum: D.now().addDays(5).toDateString(), uhrzeit: '08:15', thema: 'Brüche', ausbildungsjahr: '2' });
  const kal = await html('/kalender?jahr=2');
  assert.match(kal, /Mathe/);
  assert.match(kal, /Brüche/);
  assert.match(await html('/dashboard'), /Brüche|Mathe/);

  // Profil
  await html('/profile');
  await req('POST', '/profile', { _method: 'PATCH', vorname: 'Maxi', nachname: 'Muster', email: 'max@example.de', ausbildungsbeginn: beginn, abteilung: 'IT-Service' });
  assert.match(await html('/profile'), /IT-Service/);
  r = await req('POST', '/profile/password', { _method: 'PATCH', current_password: 'falsch', password: 'neuespasswort', password_confirmation: 'neuespasswort' });
  assert.match(await html('/profile'), /aktuelle Passwort ist falsch/);
  await req('POST', '/profile/password', { _method: 'PATCH', current_password: 'geheim123', password: 'neuespasswort', password_confirmation: 'neuespasswort' });
  assert.match(await html('/profile'), /Passwort erfolgreich/);

  // 404 fuer fremde/unbekannte IDs
  r = await req('GET', '/wochenbericht/999999');
  assert.strictEqual(r.status, 404);
});
