'use strict';
// Rendert SVG-Dateien zu PNG (Electron offscreen). Aufruf:
//   npx electron scripts/render-svg.js <eingabe.svg> <ausgabe.png> [groesse]
//   npx electron scripts/render-svg.js --vergleich <ausgabe.png> a.svg b.svg ...   (Vergleichsbild in mehreren Groessen)
const { app, BrowserWindow } = require('electron');
const fs = require('node:fs');

app.whenReady().then(async () => {
  const a = process.argv.slice(process.argv.findIndex(x => /render-svg\.js$/.test(x)) + 1);
  let html, w, h, out;
  if (a[0] === '--vergleich') {
    out = a[1];
    const svgs = a.slice(2).map(f => fs.readFileSync(f, 'utf8'));
    const groessen = [256, 64, 32, 16];
    w = 120 + svgs.length * 300; h = 560;
    const zeile = (bg) => svgs.map((s, i) => `<div style="display:flex;flex-direction:column;align-items:center;gap:14px;width:280px">
      <div style="font:600 22px sans-serif;color:${bg === '#fff' ? '#111' : '#eee'}">${String.fromCharCode(65 + i)}</div>
      <div style="display:flex;align-items:flex-end;gap:14px">${groessen.map(g => s.replace('<svg ', `<svg width="${g / (g === 256 ? 1.6 : 1)}" height="${g / (g === 256 ? 1.6 : 1)}" `)).join('')}</div></div>`).join('');
    html = `<body style="margin:0;font-family:sans-serif">
      <div style="display:flex;gap:20px;padding:30px 60px;background:#fff">${zeile('#fff')}</div>
      <div style="display:flex;gap:20px;padding:30px 60px;background:#1f1f1f">${zeile('#1f1f1f')}</div></body>`;
  } else {
    const [inp, o, g = '1024'] = a; out = o; w = h = Number(g);
    html = `<body style="margin:0;background:transparent;overflow:hidden">${fs.readFileSync(inp, 'utf8').replace('<svg ', `<svg width="${w}" height="${h}" `)}</body>`;
  }
  const win = new BrowserWindow({ width: w, height: h, show: false, useContentSize: true, transparent: true, frame: false, webPreferences: { offscreen: true } });
  const tmp = require('node:path').join(require('node:os').tmpdir(), `dan-render-${Date.now()}.html`);
  fs.writeFileSync(tmp, `<!doctype html><meta charset="utf-8">${html}`);
  await win.loadFile(tmp);
  await new Promise(r => setTimeout(r, 400));
  const img = await win.webContents.capturePage({ x: 0, y: 0, width: w, height: h });
  fs.writeFileSync(out, img.resize({ width: w, height: h }).toPNG());
  fs.rmSync(tmp, { force: true });
  console.log('geschrieben:', out);
  app.quit();
});
