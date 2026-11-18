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

test('Ersteinrichtung ohne Login und alle Seiten', async () => {
  let r = await req('GET', '/dashboard');
  assert.strictEqual(r.headers.get('location'), '/einrichten');

  r = await req('POST', '/einrichten', { vorname: 'Max', nachname: 'Muster', ausbildungsbeginn: 'kein-datum' });
  assert.strictEqual(r.status, 302);
  assert.match(await html('/einrichten'), /kein gültiges Datum/);

  const beginn = D.now().subDays(400).toDateString();
  r = await req('POST', '/einrichten', { vorname: 'Max', nachname: 'Muster', ausbildungsbeginn: beginn, ausbildungsende: D.now().addDays(700).toDateString(), abteilung: 'IT' });
  assert.strictEqual(r.headers.get('location'), '/dashboard');
  assert.match(await html('/dashboard'), /Hallo, Max/);

  // Einrichten ist danach gesperrt, ohne Sitzung geht es direkt ins Dashboard
  r = await req('GET', '/einrichten');
  assert.strictEqual(r.status, 302);
  for (const k of Object.keys(cookies)) delete cookies[k];
  assert.match(await html('/dashboard'), /Hallo, Max/);
  assert.doesNotMatch(await html('/dashboard'), /Abmelden/);

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
  await req('POST', '/profile', { _method: 'PATCH', vorname: 'Maxi', nachname: 'Muster', ausbildungsbeginn: beginn, abteilung: 'IT-Service' });
  assert.match(await html('/profile'), /IT-Service/);

  // 404 fuer fremde/unbekannte IDs
  r = await req('GET', '/wochenbericht/999999');
  assert.strictEqual(r.status, 404);
});

test('Lokaler Server nimmt nur Anfragen aus dem DAN-Fenster an', async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-guard-'));
  const geschuetzt = createApp({ dataDir: dir, appToken: 'geheim' });
  const s = await new Promise(r => { const x = geschuetzt.listen(0, '127.0.0.1', () => r(x)); });
  const url = `http://127.0.0.1:${s.address().port}`;
  try {
    assert.strictEqual((await fetch(url + '/dashboard', { redirect: 'manual' })).status, 403);
    assert.strictEqual((await fetch(url + '/einrichten', { method: 'POST', redirect: 'manual', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'vorname=X' })).status, 403);
    assert.strictEqual((await fetch(url + '/dashboard', { redirect: 'manual', headers: { Cookie: 'dan_app=falsch' } })).status, 403);
    // DNS-Rebinding: fremder Host-Header (fetch erlaubt das Setzen nicht, daher roh)
    const status = await new Promise((resolve, reject) => {
      require('node:http').get({ host: '127.0.0.1', port: s.address().port, path: '/dashboard', headers: { Cookie: 'dan_app=geheim', Host: 'boese.example' } }, (res) => { res.resume(); resolve(res.statusCode); }).on('error', reject);
    });
    assert.strictEqual(status, 403);
    assert.strictEqual((await fetch(url + '/dashboard', { redirect: 'manual', headers: { Cookie: 'dan_app=geheim' } })).status, 302);
  } finally {
    s.close(); geschuetzt.locals.db.close(); fs.rmSync(dir, { recursive: true, force: true });
  }
});

test('Datensicherung: herunterladen und in eine frische Installation zuruecklesen', async () => {
  // Sicherung aus der laufenden Test-App (mit Konto, Aufgaben, Signatur) ziehen
  let r = await req('GET', '/sicherung');
  assert.strictEqual(r.status, 200);
  assert.match(r.headers.get('content-disposition'), /DAN-Sicherung-.*\.dan/);
  const datei = Buffer.from(await r.arrayBuffer());
  assert.strictEqual(datei.subarray(0, 15).toString(), 'SQLite format 3');

  // Zweite, leere Installation
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-restore-'));
  const app2 = createApp({ dataDir: dir });
  const s2 = await new Promise(res => { const x = app2.listen(0, '127.0.0.1', () => res(x)); });
  const url2 = `http://127.0.0.1:${s2.address().port}`;
  try {
    const post = (body) => fetch(url2 + '/sicherung/wiederherstellen', { method: 'POST', redirect: 'manual', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Referer: url2 + '/einrichten' }, body: new URLSearchParams(body).toString() });
    // Kaputte Datei wird abgelehnt
    r = await post({ sicherung: 'data:application/octet-stream;base64,' + Buffer.from('kein sqlite').toString('base64') });
    assert.strictEqual(r.headers.get('location'), '/einrichten');
    // Echte Sicherung
    r = await post({ sicherung: 'data:application/octet-stream;base64,' + datei.toString('base64') });
    assert.strictEqual(r.headers.get('location'), '/dashboard');
    const dash = await (await fetch(url2 + '/dashboard')).text();
    assert.match(dash, /Hallo, Maxi/);
    const kal = await (await fetch(url2 + '/kalender?jahr=2')).text();
    assert.match(kal, /Mathe/);
    assert.strictEqual((await fetch(url2 + '/profile/signatur/bild')).status, 200);
    assert.ok(fs.readdirSync(path.join(dir, 'vorher')).length === 1, 'vorheriger Stand wurde aufgehoben');
  } finally {
    s2.close(); app2.locals.db.close(); fs.rmSync(dir, { recursive: true, force: true });
  }
});
