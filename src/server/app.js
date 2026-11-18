'use strict';
// Lokaler Webserver der DAN-App. Laeuft nur auf 127.0.0.1 innerhalb des
// Electron-Hauptprozesses; das App-Fenster laedt die Seiten von hier.

const path = require('node:path');
const fs = require('node:fs');
const express = require('express');
const ejs = require('ejs');
const { Db } = require('./db');
const { D } = require('./dates');
const { C, Group, collect } = require('./collection');
const { sessions, nurDanFenster } = require('./auth');
const logic = require('./logic');

const VIEWS = path.join(__dirname, '..', 'views');
const PUBLIC = path.join(__dirname, '..', 'public');

// ── Routen-Namen wie in Laravel (route('name', id)) ─────────────────────
const ROUTES = {
  'dashboard': '/dashboard',
  'setup': '/einrichten', 'setup.store': '/einrichten',
  'wochenbericht.index': '/wochenbericht', 'wochenbericht.aktuell': '/wochenbericht/aktuell',
  'wochenbericht.zeitraeume': '/wochenbericht/zeitraeume', 'wochenbericht.zeitraeume.erstellen': '/wochenbericht/zeitraeume',
  'wochenbericht.zeitraeume.update': '/wochenbericht/zeitraeume/:id', 'wochenbericht.zeitraeume.loeschen': '/wochenbericht/zeitraeume/:id',
  'wochenbericht.show': '/wochenbericht/:id', 'wochenbericht.speichern': '/wochenbericht/:id/speichern',
  'wochenbericht.bearbeitung.speichern': '/wochenbericht/:id/bearbeitung', 'wochenbericht.abgeben': '/wochenbericht/:id/abgeben',
  'wochenbericht.signieren': '/wochenbericht/:id/signieren', 'wochenbericht.signatur.loeschen': '/wochenbericht/:id/signatur',
  'wochenbericht.pdf': '/wochenbericht/:id/pdf', 'wochenbericht.fehlend': '/wochenbericht/fehlend-erstellen',
  'wochenbericht.sammel': '/wochenbericht/sammel-pdf',
  'kalender.index': '/kalender', 'kalender.store': '/kalender', 'kalender.update': '/kalender/:id', 'kalender.destroy': '/kalender/:id',
  'kalender.fach.store': '/kalender/fach', 'kalender.fach.destroy': '/kalender/fach/:id',
  'aufgaben.index': '/aufgaben', 'aufgaben.store': '/aufgaben', 'aufgaben.config': '/aufgaben/config',
  'aufgaben.update': '/aufgaben/:id', 'aufgaben.destroy': '/aufgaben/:id', 'aufgaben.status': '/aufgaben/:id/status',
  'aufgaben.archivieren': '/aufgaben/:id/archivieren', 'aufgaben.wiederherstellen': '/aufgaben/:id/wiederherstellen',
  'profile.edit': '/profile', 'profile.update': '/profile',
  'profile.darkmode': '/profile/darkmode', 'profile.signatur': '/profile/signatur', 'profile.signatur.bild': '/profile/signatur/bild',
  'sicherung': '/sicherung', 'sicherung.wiederherstellen': '/sicherung/wiederherstellen',
};

function route(name, param) {
  let p = ROUTES[name];
  if (!p) throw new Error(`Unbekannte Route: ${name}`);
  if (param !== undefined && param !== null && typeof param === 'object' && !(param instanceof Array)) {
    const q = { ...param };
    if ('id' in q) { p = p.replace(':id', encodeURIComponent(q.id)); delete q.id; }
    const qs = new URLSearchParams(Object.entries(q).filter(([, v]) => v != null)).toString();
    return qs ? `${p}?${qs}` : p;
  }
  if (param !== undefined) p = p.replace(':id', encodeURIComponent(param));
  return p;
}

// ── Hilfen fuer die Vorlagen (Nachbildungen der PHP-Funktionen) ─────────
const helpers = {
  route, D, collect,
  now: () => D.now(),
  today: () => D.today(),
  parseDate: (v) => D.parse(v),
  ucfirst: (s) => (s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : ''),
  count: (x) => (x == null ? 0 : x instanceof Map ? x.size : x.length ?? Object.keys(x).length),
  iter: (x) => (x == null ? [] : x instanceof Map ? x.values() : x),
  entries: (x) => (x == null ? [] : x instanceof Map ? x.entries() : Array.isArray(x) ? x.entries() : Object.entries(x)),
  round: (v, d = 0) => { const f = 10 ** d; return Math.round((Number(v) + Number.EPSILON) * f) / f; },
  min: Math.min, max: Math.max, floor: Math.floor, ceil: Math.ceil, abs: Math.abs,
  number_format: (v, d = 0, dec = '.', th = ',') => {
    const s = (Math.round(Number(v || 0) * 10 ** d) / 10 ** d).toFixed(d);
    const [i, f] = s.split('.');
    return i.replace(/\B(?=(\d{3})+(?!\d))/g, th) + (d ? dec + f : '');
  },
  /** Deutsche Zahl ohne unnoetige Nachkommastellen (z.B. 7.5 -> "7,5"). */
  zahl: (v) => (v == null ? '' : String(Math.round(Number(v) * 100) / 100).replace('.', ',')),
  strtoupper: (s) => String(s ?? '').toUpperCase(),
  substr: (s, a, l) => String(s ?? '').substr(a, l),
  str_limit: (s, l = 100) => { s = String(s ?? ''); return s.length > l ? s.slice(0, l) + '...' : s; },
  in_array: (v, arr) => Array.from(arr || []).includes(v),
  json: (v) => JSON.stringify(v ?? null).replace(/</g, '\\u003c'),
  nl2br: (s) => ejs.escapeXML(String(s ?? '')).replace(/\n/g, '<br>'),
};

function createApp({ dataDir, appToken = null }) {
  fs.mkdirSync(path.join(dataDir, 'signaturen'), { recursive: true });
  const db = new Db(path.join(dataDir, 'dan.sqlite'));
  const app = express();
  app.disable('x-powered-by');
  app.set('etag', false);
  app.use(nurDanFenster(appToken));

  app.use('/assets', express.static(path.join(PUBLIC, 'assets'), { maxAge: '1d' }));
  app.use(express.static(PUBLIC, { index: false }));
  app.use(express.urlencoded({ extended: true, limit: '60mb' }));
  app.use(express.json({ limit: '8mb' }));
  // Formular-Methodenueberschreibung (_method=PATCH/PUT/DELETE) wie Laravel.
  app.use((req, res, next) => {
    const m = req.body && req.body._method;
    if (req.method === 'POST' && typeof m === 'string' && ['PATCH', 'PUT', 'DELETE'].includes(m.toUpperCase())) req.method = m.toUpperCase();
    next();
  });
  app.use(sessions());

  // Request-Kontext: das (einzige) Konto, Flash-Daten, render()/back()
  app.use((req, res, next) => {
    req.user = logic.enrichUser(db.get('SELECT * FROM users ORDER BY id LIMIT 1'));
    const flash = req.session.flash || {};
    req.session.flash = {};
    req.wantsJson = () => /json/.test(req.get('Accept') || '') || req.xhr;

    res.flash = (key, value) => { req.session.flash[key] = value; return res; };
    res.back = (fallback = '/dashboard') => {
      const ref = req.get('Referer');
      let ziel = fallback;
      if (ref) { try { const u = new URL(ref); ziel = u.pathname + u.search + u.hash; } catch { /* ignorieren */ } }
      return res.redirect(ziel);
    };
    res.withErrors = (errors) => { req.session.flash.errors = errors; req.session.flash.old = req.body; return res.back(); };

    res.page = (view, data = {}, opts = {}) => {
      const routeName = opts.routeName || req.routeName || '';
      const locals = {
        ...helpers,
        user: req.user,
        errors: fehlerBeutel(flash.errors),
        session: (k) => flash[k] ?? null,
        old: (k, def = '') => (flash.old && flash.old[k] !== undefined ? flash.old[k] : def ?? ''),
        routeIs: (...pats) => pats.some(p => (p.endsWith('*') ? routeName.startsWith(p.slice(0, -1)) : routeName === p)),
        request: { query: (k, d = null) => req.query[k] ?? d, input: (k, d = null) => req.query[k] ?? req.body?.[k] ?? d, filled: (k) => req.query[k] != null && req.query[k] !== '' },
        ...data,
      };
      const render = (file, l) => ejs.renderFile(path.join(VIEWS, file + '.ejs'), l, { async: false, cache: !process.env.DAN_DEV, root: VIEWS, views: [VIEWS] });
      Promise.resolve()
        .then(() => render(view, locals))
        .then((body) => (opts.layout === false ? body : render(opts.layout || 'layouts/app', { ...locals, body, title: data.title })))
        .then((html) => res.type('html').send(html))
        .catch(next);
    };
    next();
  });

  const ctx = { db, logic, route, dataDir };
  // Keine Anmeldung: solange noch kein Konto eingerichtet ist, geht es zur Einrichtung.
  const guest = (req, res, next) => (req.user ? res.redirect('/dashboard') : next());
  const auth = (req, res, next) => (req.user ? next() : req.wantsJson() ? res.status(409).json({ error: 'Noch nicht eingerichtet' }) : res.redirect('/einrichten'));
  const named = (name) => (req, res, next) => { req.routeName = name; next(); };

  app.get('/', (req, res) => res.redirect(req.user ? '/dashboard' : '/einrichten'));
  require('./routes/auth')(app, ctx, { guest, auth, named });
  require('./routes/dashboard')(app, ctx, { auth, named });
  require('./routes/wochenbericht')(app, ctx, { auth, named });
  require('./routes/aufgaben')(app, ctx, { auth, named });
  require('./routes/kalender')(app, ctx, { auth, named });
  require('./routes/profile')(app, ctx, { auth, named });

  app.use((req, res) => res.status(404).type('html').send(fehlerSeite(404, 'Seite nicht gefunden.')));
  // eslint-disable-next-line no-unused-vars
  app.use((err, req, res, next) => {
    if (err.status === 404) return res.status(404).type('html').send(fehlerSeite(404, 'Nicht gefunden.'));
    console.error(err);
    res.status(500).type('html').send(fehlerSeite(500, 'Da ist etwas schiefgelaufen.', process.env.DAN_DEV ? err.stack : ''));
  });

  app.locals.db = db;
  return app;
}

/** Fehlerobjekt mit Laravel-artigen Methoden (any/first/has/all), Felder direkt lesbar. */
function fehlerBeutel(errors = {}) {
  const e = { ...errors };
  const def = (name, fn) => Object.defineProperty(e, name, { value: fn, enumerable: false });
  def('any', () => Object.keys(e).length > 0);
  def('first', (k) => (k ? e[k] ?? null : Object.values(e)[0] ?? null));
  def('has', (k) => k in e);
  def('get', (k) => (e[k] ? [e[k]] : []));
  def('all', () => Object.values(e));
  return e;
}

function fehlerSeite(code, text, details = '') {
  return `<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>${code} — DAN</title>
<link rel="stylesheet" href="/assets/app.css"></head><body style="font-family:system-ui;padding:48px;text-align:center">
<h1 style="font-size:28px;font-weight:700">${code}</h1><p style="margin:12px 0 24px">${text}</p>
<a href="/dashboard" class="btn btn-primary">Zurück zum Dashboard</a>
${details ? `<pre style="text-align:left;margin-top:24px;font-size:11px;white-space:pre-wrap">${ejs.escapeXML(details)}</pre>` : ''}</body></html>`;
}

module.exports = { createApp, route, C, Group };
