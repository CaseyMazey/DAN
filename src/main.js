'use strict';
// DAN - Desktop-Start: startet den lokalen Server im Hauptprozess und zeigt
// die App in einem eigenen Fenster. Kein XAMPP, kein PHP, keine Installation
// weiterer Software noetig.

const { app, BrowserWindow, shell, dialog, Menu, session } = require('electron');
const path = require('node:path');
const fs = require('node:fs');

// Nur eine Instanz: ein zweiter Klick aufs Icon holt das offene Fenster nach vorn.
if (!app.requestSingleInstanceLock()) {
  app.quit();
} else {
  app.on('second-instance', () => {
    const w = BrowserWindow.getAllWindows()[0];
    if (w) { if (w.isMinimized()) w.restore(); w.focus(); }
  });
  app.whenReady().then(start).catch(fehler);
}

process.removeAllListeners('warning'); // "SQLite is experimental"-Hinweis ausblenden
app.setAppUserModelId('de.dan.ausbildungsnachweis');

const PORT_WUNSCH = 47613; // fester Port, damit Einstellungen im Fenster (localStorage) erhalten bleiben
let server = null;

async function start() {
  const dataDir = path.join(app.getPath('userData'), 'daten');
  fs.mkdirSync(dataDir, { recursive: true });

  const { createApp } = require('./server/app');
  const web = createApp({ dataDir });
  const port = await lausche(web, PORT_WUNSCH).catch(() => lausche(web, 0));
  const url = `http://127.0.0.1:${port}`;

  Menu.setApplicationMenu(null);
  downloadsEinrichten();
  oeffneFenster(url, dataDir);

  app.on('window-all-closed', () => app.quit());
  app.on('before-quit', () => {
    try { server?.close(); web.locals.db.close(); } catch { /* egal beim Beenden */ }
  });
}

function lausche(web, port) {
  return new Promise((resolve, reject) => {
    const s = web.listen(port, '127.0.0.1');
    s.once('listening', () => { server = s; resolve(s.address().port); });
    s.once('error', reject);
  });
}

function oeffneFenster(url, dataDir) {
  const stateFile = path.join(app.getPath('userData'), 'fenster.json');
  let state = { width: 1320, height: 880 };
  try { state = { ...state, ...JSON.parse(fs.readFileSync(stateFile, 'utf8')) }; } catch { /* Standardgroesse */ }

  const win = new BrowserWindow({
    ...state,
    minWidth: 900,
    minHeight: 600,
    title: 'DAN',
    icon: path.join(__dirname, 'resources', 'icon.png'),
    backgroundColor: '#F4F7FB',
    show: false,
    autoHideMenuBar: true,
    webPreferences: { contextIsolation: true, nodeIntegration: false, sandbox: true, spellcheck: true },
  });
  if (state.maximized) win.maximize();
  win.once('ready-to-show', () => win.show());

  // Fenstergroesse merken
  const speichern = () => {
    if (win.isDestroyed()) return;
    const b = win.getNormalBounds();
    fs.writeFile(stateFile, JSON.stringify({ ...b, maximized: win.isMaximized() }), () => {});
  };
  win.on('close', speichern);

  // Externe Links im normalen Browser oeffnen, nie im App-Fenster.
  const intern = (u) => u.startsWith(url);
  win.webContents.setWindowOpenHandler(({ url: ziel }) => {
    if (!intern(ziel) && /^https?:/.test(ziel)) shell.openExternal(ziel);
    return { action: 'deny' };
  });
  win.webContents.on('will-navigate', (e, ziel) => {
    if (!intern(ziel)) { e.preventDefault(); if (/^https?:/.test(ziel)) shell.openExternal(ziel); }
  });

  // F5 = neu laden, Strg+P = drucken, F12 = Entwicklerwerkzeuge (Fehlersuche)
  win.webContents.on('before-input-event', (e, input) => {
    if (input.type !== 'keyDown') return;
    if (input.key === 'F5') { win.webContents.reload(); e.preventDefault(); }
    if (input.key === 'F12') { win.webContents.toggleDevTools(); e.preventDefault(); }
    if (input.control && input.key.toLowerCase() === 'p') { win.webContents.print(); e.preventDefault(); }
  });

  win.loadURL(url);
  void dataDir;
}

/** PDF-Downloads: Speichern-Dialog im Downloads-Ordner, danach Datei oeffnen. */
function downloadsEinrichten() {
  session.defaultSession.on('will-download', (event, item) => {
    item.setSaveDialogOptions({
      title: 'PDF speichern',
      defaultPath: path.join(app.getPath('downloads'), item.getFilename()),
      filters: [{ name: 'PDF', extensions: ['pdf'] }],
    });
    item.once('done', (e, state) => {
      if (state === 'completed') shell.openPath(item.getSavePath());
    });
  });
}

function fehler(err) {
  dialog.showErrorBox('DAN konnte nicht starten', String(err && err.stack || err));
  app.quit();
}
