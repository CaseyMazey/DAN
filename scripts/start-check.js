'use strict';
// Startet die GEBAUTE App mit einem leeren Test-Datenordner und prueft:
//   1. das Fenster laedt die DAN-Einrichtungsseite,
//   2. der lokale Server weist Anfragen von aussen ab (403).
// Aufruf: node scripts/start-check.js <Pfad zur ausfuehrbaren Datei>
// Unter Linux ohne Bildschirm wird automatisch xvfb-run benutzt.

const { spawn } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const bin = process.argv[2];
if (!bin || !fs.existsSync(bin)) { console.error(`Programm nicht gefunden: ${bin}`); process.exit(2); }

const userData = fs.mkdtempSync(path.join(os.tmpdir(), 'dan-start-'));
const port = 9400 + Math.floor(Math.random() * 400);
const args = [`--remote-debugging-port=${port}`];
if (process.platform === 'linux') args.push('--no-sandbox'); // CI-Runner erlauben keine Chromium-Sandbox

const [cmd, cmdArgs] = process.platform === 'linux' && !process.env.DISPLAY ? ['xvfb-run', ['-a', bin, ...args]] : [bin, args];
const child = spawn(cmd, cmdArgs, { env: { ...process.env, DAN_USERDATA: userData }, stdio: ['ignore', 'pipe', 'pipe'], detached: process.platform !== 'win32' });
// Hartes Zeitlimit, falls irgendetwas haengt
setTimeout(() => { console.error('START-CHECK: Zeitlimit erreicht'); console.error(log.slice(-4000)); finish(1); }, 120000).unref();
let log = '';
child.stdout.on('data', d => { log += d; });
child.stderr.on('data', d => { log += d; });

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

async function main() {
  let page = null;
  for (let i = 0; i < 60 && !page; i++) {
    await sleep(1000);
    try {
      const list = await (await fetch(`http://127.0.0.1:${port}/json`)).json();
      page = list.find(p => p.type === 'page' && /^http:\/\/127\.0\.0\.1:\d+\//.test(p.url) && /DAN/.test(p.title));
    } catch { /* noch nicht bereit */ }
  }
  if (!page) throw new Error('Das DAN-Fenster ist nicht innerhalb von 60 s erschienen.');
  console.log(`Fenster: "${page.title}" ${page.url}`);
  if (!/\/einrichten$/.test(page.url)) throw new Error(`Erwartet wurde die Einrichtungsseite, geladen ist ${page.url}`);

  const base = new URL(page.url).origin;
  const status = (await fetch(base + '/dashboard', { redirect: 'manual' })).status;
  console.log(`Zugriff von aussen: ${status}`);
  if (status !== 403) throw new Error('Der lokale Server ist von aussen erreichbar!');
  if (!fs.existsSync(path.join(userData, 'daten', 'dan.sqlite'))) throw new Error('Datenbank wurde nicht angelegt.');
  console.log('Datenbank angelegt: ok');
}

main()
  .then(() => { console.log('START-CHECK OK'); finish(0); })
  .catch((e) => { console.error('START-CHECK FEHLGESCHLAGEN:', e.message); console.error(log.slice(-4000)); finish(1); });

function finish(code) {
  try {
    if (process.platform === 'win32') spawn('taskkill', ['/pid', String(child.pid), '/t', '/f']);
    else process.kill(-child.pid, 'SIGKILL'); // ganze Prozessgruppe (xvfb-run, Xvfb, Electron)
  } catch { /* egal */ }
  setTimeout(() => {
    try { fs.rmSync(userData, { recursive: true, force: true }); } catch { /* egal */ }
    process.exit(code);
  }, 1500);
}
