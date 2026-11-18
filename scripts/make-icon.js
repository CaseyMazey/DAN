'use strict';
// Erzeugt alle Icon-Dateien aus build/icon.svg:
//   build/icon.png (1024, fuer Installer/macOS/Linux), src/resources/icon.png (512, Fenster),
//   src/public/favicon.svg / favicon-16x16.png / favicon-32x32.png / favicon.ico
// Aufruf: npx electron scripts/make-icon.js
const { app, BrowserWindow } = require('electron');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '..');
const svg = fs.readFileSync(path.join(root, 'build', 'icon.svg'), 'utf8');

let win = null;
/** Zeichnet das SVG auf eine Canvas der gewuenschten Groesse (unabhaengig von Fenster-/Bildschirmgroesse). */
async function render(size) {
  if (!win) {
    win = new BrowserWindow({ width: 200, height: 200, show: false, webPreferences: { offscreen: true } });
    await win.loadURL('about:blank');
  }
  const b64 = await win.webContents.executeJavaScript(`new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      const c = document.createElement('canvas'); c.width = c.height = ${size};
      const ctx = c.getContext('2d'); ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(img, 0, 0, ${size}, ${size});
      resolve(c.toDataURL('image/png').split(',')[1]);
    };
    img.onerror = reject;
    img.src = 'data:image/svg+xml;base64,' + ${JSON.stringify(Buffer.from(svg).toString('base64'))};
  })`);
  return Buffer.from(b64, 'base64');
}

/** ICO-Datei mit PNG-Eintraegen (von Windows ab Vista unterstuetzt). */
function ico(pngs) {
  const kopf = Buffer.alloc(6); kopf.writeUInt16LE(0, 0); kopf.writeUInt16LE(1, 2); kopf.writeUInt16LE(pngs.length, 4);
  const eintraege = []; let offset = 6 + 16 * pngs.length;
  for (const { size, data } of pngs) {
    const e = Buffer.alloc(16);
    e.writeUInt8(size >= 256 ? 0 : size, 0); e.writeUInt8(size >= 256 ? 0 : size, 1);
    e.writeUInt16LE(1, 4); e.writeUInt16LE(32, 6); e.writeUInt32LE(data.length, 8); e.writeUInt32LE(offset, 12);
    eintraege.push(e); offset += data.length;
  }
  return Buffer.concat([kopf, ...eintraege, ...pngs.map(p => p.data)]);
}

app.on('window-all-closed', () => {}); // zwischen den Renderdurchlaeufen nicht beenden
app.whenReady().then(async () => {
  const w = (rel, data) => { fs.writeFileSync(path.join(root, rel), data); console.log('geschrieben:', rel); };
  w('build/icon.png', await render(1024));
  w('src/resources/icon.png', await render(512));
  const p16 = await render(16), p32 = await render(32), p48 = await render(48), p256 = await render(256);
  w('src/public/favicon-16x16.png', p16);
  w('src/public/favicon-32x32.png', p32);
  w('src/public/favicon.ico', ico([{ size: 16, data: p16 }, { size: 32, data: p32 }, { size: 48, data: p48 }, { size: 256, data: p256 }]));
  w('src/public/favicon.svg', svg);
  app.quit();
});
