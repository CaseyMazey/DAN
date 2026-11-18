'use strict';
// DAN ist eine Einzelplatz-App ohne Anmeldung: Schutz bietet das Windows-Konto.
// Der lokale Server darf aber nur vom DAN-Fenster benutzt werden - sonst
// koennte jede Webseite im Browser Anfragen an 127.0.0.1 schicken (CSRF) oder
// per DNS-Rebinding Daten lesen. Deshalb:
//   - das Electron-Fenster bekommt beim Start ein zufaelliges Geheimnis als
//     Cookie (nur dort gesetzt, nie an den Browser ausgegeben),
//   - jede Anfrage ohne dieses Cookie oder mit fremdem Host-Header wird abgelehnt.
// Daneben gibt es eine kleine Sitzung im Speicher fuer Flash-Meldungen.

const crypto = require('node:crypto');

const randomToken = () => crypto.randomBytes(32).toString('hex');

function parseCookies(header) {
  const out = {};
  for (const part of (header || '').split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

function sicherGleich(a, b) {
  const x = Buffer.from(String(a || '')), y = Buffer.from(String(b || ''));
  return x.length === y.length && crypto.timingSafeEqual(x, y);
}

/** Middleware: nur Anfragen aus dem DAN-Fenster zulassen. Ohne appToken (Entwicklung/Tests) offen. */
function nurDanFenster(appToken) {
  return function (req, res, next) {
    if (!appToken) return next();
    const host = String(req.headers.host || '');
    const hostOk = /^(127\.0\.0\.1|localhost)(:\d+)?$/.test(host);
    if (hostOk && sicherGleich(parseCookies(req.headers.cookie).dan_app, appToken)) return next();
    res.status(403).type('text').send('DAN kann nur über das DAN-Fenster geöffnet werden.');
  };
}

/** Middleware: haengt req.session an (Flash-Daten). */
function sessions() {
  const store = new Map();
  return function (req, res, next) {
    const sid = parseCookies(req.headers.cookie).dan_sid;
    let s = sid && store.get(sid);
    if (!s) {
      const neu = randomToken();
      s = { flash: {} };
      store.set(neu, s);
      res.append('Set-Cookie', `dan_sid=${neu}; Path=/; HttpOnly; SameSite=Strict`);
    }
    req.session = s;
    next();
  };
}

module.exports = { sessions, nurDanFenster, randomToken, parseCookies };
