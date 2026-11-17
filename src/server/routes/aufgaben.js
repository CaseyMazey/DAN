'use strict';
const { D } = require('../dates');
const { validate, viertelstunde } = require('../validate');
const { orFail } = require('../app-helpers');

module.exports = (app, { db, logic }, { auth, named }) => {
  const aufgabe = (req) => orFail(db.get('SELECT * FROM aufgaben WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
  const regeln = {
    titel: 'required|string|max:255',
    beschreibung: 'nullable|string|max:2000',
    kategorie: 'required|in:betrieb,berufsschule,unterweisung',
    stunden: ['required', 'numeric', 'min:0.5', 'max:99', viertelstunde],
    stunden_abziehen_bei: 'required_if:kategorie,unterweisung|nullable|in:betrieb,berufsschule',
  };

  app.get('/aufgaben', auth, named('aufgaben.index'), (req, res) => {
    const user = req.user;
    let config = logic.stundenConfig(db, user.id);
    if (!config) {
      db.insert('stunden_config', { azubi_id: user.id, max_stunden: 42, betrieb_stunden: 32, berufsschule_stunden: 10 });
      config = logic.stundenConfig(db, user.id);
    }
    logic.schliesseVorwocheAbFallsNoetig(db, user);
    const aufgaben = db.all('SELECT * FROM aufgaben WHERE azubi_id = ? AND archiviert = 0 ORDER BY id', user.id).groupBy('status');
    const archiviert = db.all('SELECT * FROM aufgaben WHERE azubi_id = ? AND archiviert = 1 ORDER BY archiviert_am DESC LIMIT 5', user.id);
    const stundenDieseWoche = db.value("SELECT COALESCE(SUM(stunden),0) FROM aufgaben WHERE azubi_id = ? AND archiviert = 0 AND status IN ('in_bearbeitung','fertig')", user.id);
    const ueberschritten = stundenDieseWoche > config.max_stunden;
    res.page('aufgaben/index', { title: 'Aufgaben', aufgaben, archiviert, config, stundenDieseWoche, ueberschritten });
  });

  app.post('/aufgaben', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, regeln);
    if (!ok) return res.withErrors(errors);
    db.insert('aufgaben', {
      azubi_id: req.user.id, titel: data.titel, beschreibung: data.beschreibung, kategorie: data.kategorie,
      stunden: data.stunden, stunden_abziehen_bei: data.stunden_abziehen_bei, status: 'geplant',
    });
    res.flash('success', 'Aufgabe erstellt.').back();
  });

  // Muss vor /aufgaben/:id stehen.
  app.patch('/aufgaben/config', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, {
      max_stunden: 'required|numeric|min:1|max:80',
      betrieb_stunden: 'required|numeric|min:0|max:80',
      berufsschule_stunden: 'required|numeric|min:0|max:80',
    });
    if (!ok) return res.withErrors(errors);
    const c = logic.stundenConfig(db, req.user.id);
    if (c) db.update('stunden_config', c.id, data);
    else db.insert('stunden_config', { azubi_id: req.user.id, ...data });
    res.flash('success', 'Stunden-Konfiguration gespeichert.').back();
  });

  app.patch('/aufgaben/:id', auth, (req, res) => {
    const a = aufgabe(req);
    const { ok, errors, data } = validate(req.body, regeln);
    if (!ok) return res.withErrors(errors);
    db.update('aufgaben', a.id, {
      titel: data.titel, beschreibung: data.beschreibung, kategorie: data.kategorie,
      stunden: data.stunden, stunden_abziehen_bei: data.stunden_abziehen_bei,
    });
    res.flash('success', 'Aufgabe aktualisiert.').back();
  });

  app.delete('/aufgaben/:id', auth, (req, res) => {
    db.run('DELETE FROM aufgaben WHERE id = ?', aufgabe(req).id);
    res.flash('success', 'Aufgabe gelöscht.').back();
  });

  app.patch('/aufgaben/:id/status', auth, (req, res) => {
    const a = aufgabe(req);
    const { ok, errors, data } = validate(req.body, { status: 'required|in:geplant,in_bearbeitung,fertig' });
    if (!ok) return req.wantsJson() ? res.status(422).json({ errors }) : res.withErrors(errors);
    db.update('aufgaben', a.id, { status: data.status });
    if (req.wantsJson()) return res.json({ success: true });
    res.flash('success', 'Status geändert.').back();
  });

  app.patch('/aufgaben/:id/archivieren', auth, (req, res) => {
    db.update('aufgaben', aufgabe(req).id, { archiviert: true, archiviert_am: D.now() });
    res.flash('success', 'Aufgabe archiviert.').back();
  });

  app.patch('/aufgaben/:id/wiederherstellen', auth, (req, res) => {
    db.update('aufgaben', aufgabe(req).id, { archiviert: false, archiviert_am: null, in_wochenbericht: false });
    res.flash('success', 'Aufgabe wiederhergestellt.').back();
  });
};
