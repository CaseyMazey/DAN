'use strict';
// Passwort-Hashing (scrypt, in Node eingebaut) und eine einfache Sitzungs-
// verwaltung. Es gibt genau ein lokales Konto; die Sitzung liegt im Speicher,
// "Angemeldet bleiben" setzt zusaetzlich ein dauerhaftes Token-Cookie.

const crypto = require('node:crypto');

const N = 16384, R = 8, P = 1, KEYLEN = 64;

function hashPassword(pw) {
  const salt = crypto.randomBytes(16);
  const hash = crypto.scryptSync(String(pw), salt, KEYLEN, { N, r: R, p: P });
  return `scrypt$${N}$${salt.toString('base64')}$${hash.toString('base64')}`;
}

function verifyPassword(pw, stored) {
  if (!stored) return false;
  // Aus DAN2 (Laravel) importierte Konten haben noch einen bcrypt-Hash.
  if (/^\$2[aby]\$/.test(stored)) return require('bcryptjs').compareSync(String(pw), stored);
  if (!stored.startsWith('scrypt$')) return false;
  const [, n, saltB64, hashB64] = stored.split('$');
  const expected = Buffer.from(hashB64, 'base64');
  const actual = crypto.scryptSync(String(pw), Buffer.from(saltB64, 'base64'), expected.length, { N: Number(n), r: R, p: P });
  return crypto.timingSafeEqual(expected, actual);
}

const sha256 = (s) => crypto.createHash('sha256').update(s).digest('hex');
const randomToken = () => crypto.randomBytes(32).toString('hex');

function parseCookies(header) {
  const out = {};
  for (const part of (header || '').split(';')) {
    const i = part.indexOf('=');
    if (i > 0) out[part.slice(0, i).trim()] = decodeURIComponent(part.slice(i + 1).trim());
  }
  return out;
}

function cookie(name, value, { maxAge } = {}) {
  let c = `${name}=${encodeURIComponent(value)}; Path=/; HttpOnly; SameSite=Strict`;
  if (maxAge != null) c += `; Max-Age=${maxAge}`;
  return c;
}

/** Middleware: haengt req.session an (Flash-Daten, userId). */
function sessions(db) {
  const store = new Map();
  return function (req, res, next) {
    const cookies = parseCookies(req.headers.cookie);
    let sid = cookies.dan_sid;
    let s = sid && store.get(sid);
    if (!s) {
      sid = randomToken();
      s = { flash: {}, userId: null };
      store.set(sid, s);
      res.append('Set-Cookie', cookie('dan_sid', sid));
    }
    // "Angemeldet bleiben": dauerhaftes Token gegen den gespeicherten Hash pruefen.
    if (!s.userId && cookies.dan_remember) {
      const u = db.get('SELECT id FROM users WHERE remember_token = ?', sha256(cookies.dan_remember));
      if (u) s.userId = u.id;
    }
    req.session = s;
    req.sessionId = sid;
    req.regenerate = () => {
      store.delete(req.sessionId);
      const neu = randomToken();
      store.set(neu, s);
      req.sessionId = neu;
      res.append('Set-Cookie', cookie('dan_sid', neu));
    };
    req.destroySession = () => {
      store.delete(req.sessionId);
      res.append('Set-Cookie', cookie('dan_sid', '', { maxAge: 0 }));
      res.append('Set-Cookie', cookie('dan_remember', '', { maxAge: 0 }));
    };
    next();
  };
}

function rememberCookie(res, db, userId) {
  const token = randomToken();
  db.run('UPDATE users SET remember_token = ? WHERE id = ?', sha256(token), userId);
  res.append('Set-Cookie', cookie('dan_remember', token, { maxAge: 60 * 60 * 24 * 365 }));
}

const needsRehash = (stored) => !String(stored || '').startsWith('scrypt$');

module.exports = { hashPassword, verifyPassword, needsRehash, sessions, rememberCookie, parseCookies };
