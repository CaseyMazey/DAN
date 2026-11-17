'use strict';
const { D } = require('../dates');
const { validate } = require('../validate');
const { hashPassword, verifyPassword, needsRehash, rememberCookie } = require('../auth');

// Einfache Sperre gegen Durchprobieren: 5 Fehlversuche -> 60 s Pause.
const versuche = { n: 0, bis: 0 };

module.exports = (app, { db }, { guest, auth }) => {
  const kontoVorhanden = () => !!db.get('SELECT id FROM users LIMIT 1');

  app.get('/einrichten', guest, (req, res) => {
    if (kontoVorhanden()) return res.redirect('/login');
    res.page('auth/setup', { title: 'Konto einrichten' }, { layout: 'layouts/guest' });
  });

  app.post('/einrichten', guest, (req, res) => {
    if (kontoVorhanden()) return res.redirect('/login');
    const { ok, errors, data } = validate(req.body, {
      vorname: 'required|string|max:255',
      nachname: 'required|string|max:255',
      email: 'required|email|max:255',
      password: 'required|confirmed|min:8',
      ausbildungsbeginn: 'required|date',
      ausbildungsende: 'nullable|date|after:ausbildungsbeginn',
    });
    if (!ok) return res.withErrors(errors);
    const id = db.insert('users', {
      vorname: data.vorname,
      nachname: data.nachname,
      email: data.email.toLowerCase(),
      password: hashPassword(req.body.password),
      ausbildungsbeginn: data.ausbildungsbeginn,
      ausbildungsende: data.ausbildungsende,
    });
    req.regenerate();
    req.session.userId = id;
    rememberCookie(res, db, id);
    res.flash('success', 'Willkommen bei DAN!').redirect('/dashboard');
  });

  app.get('/login', guest, (req, res) => {
    if (!kontoVorhanden()) return res.redirect('/einrichten');
    res.page('auth/login', { title: 'Anmelden' }, { layout: 'layouts/guest' });
  });

  app.post('/login', guest, (req, res) => {
    if (Date.now() < versuche.bis) {
      const s = Math.ceil((versuche.bis - Date.now()) / 1000);
      return res.withErrors({ email: `Zu viele Anmeldeversuche. Bitte in ${s} Sekunden erneut versuchen.` });
    }
    const email = String(req.body.email || '').trim().toLowerCase();
    const u = db.get('SELECT * FROM users WHERE email = ?', email);
    if (!u || !verifyPassword(req.body.password || '', u.password)) {
      if (++versuche.n >= 5) { versuche.n = 0; versuche.bis = Date.now() + 60000; }
      return res.withErrors({ email: 'E-Mail oder Passwort ist falsch.' });
    }
    versuche.n = 0;
    req.regenerate();
    req.session.userId = u.id;
    if (req.body.remember) rememberCookie(res, db, u.id);
    // Alten bcrypt-Hash (Import aus DAN2) beim ersten Login auf scrypt umstellen.
    db.update('users', u.id, needsRehash(u.password) ? { password: hashPassword(req.body.password) } : {});
    res.redirect('/dashboard');
  });

  app.post('/logout', auth, (req, res) => {
    db.run('UPDATE users SET remember_token = NULL WHERE id = ?', req.user.id);
    req.session.userId = null;
    req.destroySession();
    res.redirect('/login');
  });

  void D;
};
