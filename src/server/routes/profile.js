'use strict';
const fs = require('node:fs');
const path = require('node:path');
const { validate } = require('../validate');
const { hashPassword, verifyPassword } = require('../auth');

module.exports = (app, { db, dataDir }, { auth, named }) => {
  app.get('/profile', auth, named('profile.edit'), (req, res) => {
    res.page('profile', { title: 'Mein Profil' });
  });

  app.patch('/profile', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, {
      vorname: 'required|string|max:255',
      nachname: 'required|string|max:255',
      email: 'required|email|max:255',
      ausbildungsbeginn: 'nullable|date',
      ausbildungsende: 'nullable|date|after:ausbildungsbeginn',
      abteilung: 'nullable|string|max:255',
    });
    if (!ok) return res.withErrors(errors);
    db.update('users', req.user.id, {
      vorname: data.vorname, nachname: data.nachname, email: data.email.toLowerCase(),
      ausbildungsbeginn: data.ausbildungsbeginn, ausbildungsende: data.ausbildungsende,
      abteilung: data.abteilung,
    });
    res.flash('status', 'profile-updated').redirect('/profile');
  });

  app.patch('/profile/password', auth, (req, res) => {
    const { ok, errors } = validate(req.body, {
      current_password: ['required', (v) => (verifyPassword(v, req.user.password) ? null : 'Das aktuelle Passwort ist falsch.')],
      password: 'required|confirmed|min:8',
    });
    if (!ok) return res.withErrors(errors);
    db.update('users', req.user.id, { password: hashPassword(req.body.password) });
    res.flash('status', 'password-updated').redirect('/profile#sec-pw');
  });

  app.patch('/profile/darkmode', auth, (req, res) => {
    db.update('users', req.user.id, { dark_mode: req.body.dark_mode === '1' || req.body.dark_mode === 'on' || req.body.dark_mode === true });
    if (req.wantsJson() || req.xhr) return res.json({ success: true });
    res.redirect('/profile');
  });

  app.patch('/profile/signatur', auth, (req, res) => {
    const daten = String(req.body.signatur_canvas || '');
    const m = daten.match(/^data:image\/(png|jpe?g);base64,([A-Za-z0-9+/=]+)$/);
    if (!m || daten.length > 2900000) return res.withErrors({ signatur_canvas: 'Die Signatur konnte nicht als Bild erkannt werden.' });
    const bytes = Buffer.from(m[2], 'base64');
    const istPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4e;
    const istJpg = bytes[0] === 0xff && bytes[1] === 0xd8;
    if (!istPng && !istJpg) return res.withErrors({ signatur_canvas: 'Die Signatur konnte nicht als Bild erkannt werden.' });

    const rel = `signaturen/${req.user.id}_${Date.now()}.${istPng ? 'png' : 'jpg'}`;
    fs.writeFileSync(path.join(dataDir, rel), bytes);
    if (req.user.signatur_pfad) {
      const alt = path.join(dataDir, req.user.signatur_pfad);
      if (alt.startsWith(path.join(dataDir, 'signaturen'))) fs.rmSync(alt, { force: true });
    }
    db.update('users', req.user.id, { signatur_pfad: rel });
    res.flash('status', 'signatur-updated').redirect('/profile#sec-sig');
  });

  app.get('/profile/signatur/bild', auth, (req, res) => {
    const p = req.user.signatur_pfad && path.join(dataDir, req.user.signatur_pfad);
    if (!p || !p.startsWith(path.join(dataDir, 'signaturen')) || !fs.existsSync(p)) return res.status(404).end();
    res.set('Cache-Control', 'no-store').sendFile(p);
  });
};
