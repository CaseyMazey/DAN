'use strict';
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const { validate } = require('../validate');
const { erstelleSicherung, stelleWiederHer } = require('../sicherung');
const { D } = require('../dates');

module.exports = (app, { db, dataDir, updater, version }, { auth, named }) => {
  app.get('/profile', auth, named('profile.edit'), (req, res) => {
    res.page('profile', { title: 'Mein Profil', appVersion: version, update: updater ? updater.state : { status: 'off' } });
  });

  // ── Updates: suchen, bzw. installieren / herunterladen, wenn eins bereitliegt ──
  app.post('/update', auth, async (req, res) => {
    if (!updater) return res.redirect('/profile#sec-update');
    const s = updater.state.status;
    if (s === 'ready' || s === 'available') { updater.install(); return res.redirect('/profile#sec-update'); }
    await updater.check(true);
    // kurz warten, damit "keine neue Version" oder "wird geladen" gleich angezeigt wird
    await new Promise((r) => setTimeout(r, 1500));
    res.redirect('/profile#sec-update');
  });

  // ── Datensicherung ────────────────────────────────────────────────────
  // Auch ohne eingerichtetes Konto erreichbar, damit man direkt nach der
  // Installation eine Sicherung zuruecklesen kann.
  app.get('/sicherung', (req, res, next) => {
    const tmp = path.join(os.tmpdir(), `dan-sicherung-${Date.now()}.dan`);
    try {
      erstelleSicherung(db, dataDir, tmp);
      res.download(tmp, `DAN-Sicherung-${D.now().format('Y-m-d')}.dan`, () => fs.rmSync(tmp, { force: true }));
    } catch (e) { fs.rmSync(tmp, { force: true }); next(e); }
  });

  app.post('/sicherung/wiederherstellen', (req, res) => {
    const m = String(req.body.sicherung || '').match(/^data:[^;]*;base64,([A-Za-z0-9+/=]+)$/);
    if (!m) return res.withErrors({ sicherung: 'Bitte eine Sicherungsdatei (.dan) auswählen.' });
    const tmp = path.join(os.tmpdir(), `dan-wiederherstellen-${Date.now()}.dan`);
    try {
      fs.writeFileSync(tmp, Buffer.from(m[1], 'base64'));
      const info = stelleWiederHer(db, dataDir, tmp);
      res.flash('success', `Sicherung wiederhergestellt: ${info.berichte} Wochenberichte, ${info.aufgaben} Aufgaben.`).redirect('/dashboard');
    } catch (e) {
      res.withErrors({ sicherung: e.message || 'Die Sicherung konnte nicht wiederhergestellt werden.' });
    } finally {
      fs.rmSync(tmp, { force: true });
    }
  });

  app.patch('/profile', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, {
      vorname: 'required|string|max:255',
      nachname: 'required|string|max:255',
      ausbildungsbeginn: 'nullable|date',
      ausbildungsende: 'nullable|date|after:ausbildungsbeginn',
      abteilung: 'nullable|string|max:255',
    });
    if (!ok) return res.withErrors(errors);
    db.update('users', req.user.id, {
      vorname: data.vorname, nachname: data.nachname,
      ausbildungsbeginn: data.ausbildungsbeginn, ausbildungsende: data.ausbildungsende,
      abteilung: data.abteilung,
    });
    res.flash('status', 'profile-updated').redirect('/profile');
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
