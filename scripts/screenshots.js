'use strict';
// Erzeugt die README-Screenshots mit erfundenen Beispieldaten (nie mit echten Daten).
// Aufruf: npx electron scripts/screenshots.js
// Ergebnis: docs/screenshots/*.png

const { app, BrowserWindow } = require('electron');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { createApp } = require('../src/server/app');
const { D } = require('../src/server/dates');

const ZIEL = path.join(__dirname, '..', 'docs', 'screenshots');
const W = 1400, H = 900;

async function beispieldaten(base) {
  const jar = {};
  const req = async (method, url, form, json) => {
    const headers = { Cookie: Object.entries(jar).map(([k, v]) => `${k}=${v}`).join('; '), Referer: base + '/dashboard' };
    let body;
    if (form) { headers['Content-Type'] = 'application/x-www-form-urlencoded'; body = new URLSearchParams(form).toString(); }
    if (json) headers.Accept = 'application/json';
    const r = await fetch(base + url, { method, headers, body, redirect: 'manual' });
    for (const c of r.headers.getSetCookie()) { const [kv] = c.split(';'); const [k, v] = kv.split('='); jar[k] = v; }
    return r;
  };
  const tag = (n) => D.now().addDays(n).toDateString();
  await req('POST', '/einrichten', { vorname: 'Max', nachname: 'Mustermann', abteilung: 'IT Anwendungsentwicklung', ausbildungsbeginn: D.now().subDays(470).toDateString(), ausbildungsende: D.now().addDays(625).toDateString() });
  const aufgaben = [
    ['Kundenportal: Login-Seite umsetzen', 'React-Komponenten und Formularprüfung', 'betrieb', '12', '', 'in_bearbeitung'],
    ['Code-Review mit dem Team', 'Pull Requests der Woche besprochen', 'betrieb', '3', '', 'fertig'],
    ['Datenbank-Backup automatisieren', 'Cronjob und Dokumentation', 'betrieb', '6', '', 'geplant'],
    ['Arbeitsschutz-Unterweisung', 'Jährliche Pflichtschulung', 'unterweisung', '2', 'betrieb', 'fertig'],
    ['LF 8: Objektorientierung', 'Klassen, Vererbung, Interfaces', 'berufsschule', '6', '', 'in_bearbeitung'],
    ['LF 9: Netzwerke', 'Subnetting üben', 'berufsschule', '4', '', 'geplant'],
    ['Englisch: Präsentation vorbereiten', 'Thema: Agile Methoden', 'berufsschule', '2', '', 'geplant'],
  ];
  for (const [titel, beschreibung, kategorie, stunden, abzug] of aufgaben) {
    await req('POST', '/aufgaben', { titel, beschreibung, kategorie, stunden, stunden_abziehen_bei: abzug });
  }
  const seite = await (await req('GET', '/aufgaben')).text();
  const ids = [...seite.matchAll(/data-id="(\d+)" data-titel="([^"]+)"/g)].map(m => ({ id: m[1], titel: m[2] }));
  for (const [titel, , , , , status] of aufgaben) {
    const a = ids.find(x => x.titel === titel);
    if (a && status !== 'geplant') await req('POST', `/aufgaben/${a.id}/status`, { _method: 'PATCH', status }, true);
  }
  for (const fach of ['LF 8', 'LF 9', 'LF 10', 'Englisch', 'PoWi']) await req('POST', '/kalender/fach', { name: fach }, true);
  await req('POST', '/kalender', { fach: 'LF 8', typ: 'klassenarbeit', datum: tag(-21), note: '1.5' });
  await req('POST', '/kalender', { fach: 'Englisch', typ: 'test', datum: tag(-9), note: '2' });
  await req('POST', '/kalender', { fach: 'PoWi', typ: 'klassenarbeit', datum: tag(-40), note: '2.5' });
  await req('POST', '/kalender', { fach: 'LF 9', typ: 'klassenarbeit', datum: tag(6), uhrzeit: '08:00', thema: 'Subnetting' });
  await req('POST', '/kalender', { fach: 'LF 10', typ: 'projekt', datum: tag(13), thema: 'Projektabgabe Webshop' });
  await req('POST', '/wochenbericht/zeitraeume', { von_datum: tag(-80), bis_datum: tag(-70), bezeichnung: 'Urlaub' });
  return req;
}

app.on('window-all-closed', () => {});
app.whenReady().then(async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-shots-'));
  const web = createApp({ dataDir: dir });
  const server = await new Promise(r => { const s = web.listen(0, '127.0.0.1', () => r(s)); });
  const base = `http://127.0.0.1:${server.address().port}`;
  await beispieldaten(base);
  fs.mkdirSync(ZIEL, { recursive: true });

  const win = new BrowserWindow({ width: W, height: H, useContentSize: true, show: false, webPreferences: { offscreen: true } });
  const seiten = [
    ['dashboard', '/dashboard', false],
    ['wochenbericht', '/wochenbericht', false],
    ['aufgaben', '/aufgaben', false],
    ['noten', '/kalender', false],
    ['dashboard-dunkel', '/dashboard', true],
  ];
  for (const [name, url, dunkel] of seiten) {
    await win.loadURL(base + url);
    await win.webContents.executeJavaScript(`localStorage.setItem('dan_dark', '${dunkel ? 1 : 0}'); document.documentElement.setAttribute('data-theme', '${dunkel ? 'dark' : ''}'); document.fonts.ready`);
    await new Promise(r => setTimeout(r, 600));
    const img = await win.webContents.capturePage();
    fs.writeFileSync(path.join(ZIEL, `${name}.png`), img.toPNG());
    console.log('geschrieben:', `docs/screenshots/${name}.png`, img.getSize());
  }
  server.close(); web.locals.db.close();
  fs.rmSync(dir, { recursive: true, force: true });
  app.quit();
});
