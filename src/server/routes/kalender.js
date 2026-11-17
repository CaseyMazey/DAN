'use strict';
const { D } = require('../dates');
const { collect } = require('../collection');
const { validate } = require('../validate');
const { orFail } = require('../app-helpers');

module.exports = (app, { db, logic }, { auth, named }) => {
  const gueltigesJahr = (user, angefordert) => {
    const n = angefordert == null || angefordert === '' ? null : parseInt(angefordert, 10);
    return n !== null && n >= 1 && n <= logic.anzahlAusbildungsjahre(user) ? n : logic.aktuellesAusbildungsjahr(user);
  };
  const regeln = {
    fach: 'required|string|max:255',
    typ: 'required|in:test,klassenarbeit,hausaufgabe,projekt,sonstiges',
    datum: 'required|date',
    uhrzeit: 'nullable|date_format:H:i',
    thema: 'nullable|string|max:255',
    lernnotizen: 'nullable|string|max:2000',
    note: 'nullable|numeric|min:1|max:6',
  };

  app.get('/kalender', auth, named('kalender.index'), (req, res) => {
    const user = req.user;
    const jahre = new Map();
    for (let n = 1; n <= logic.anzahlAusbildungsjahre(user); n++) {
      let label = null;
      if (user.ausbildungsbeginn) {
        const s = user.ausbildungsbeginn.addYears(n - 1), e = user.ausbildungsbeginn.addYears(n);
        label = s.year === e.year ? String(s.year) : `${s.year}/${e.year}`;
      }
      jahre.set(n, label);
    }
    let jahr = parseInt(req.query.jahr ?? logic.aktuellesAusbildungsjahr(user), 10);
    if (!jahre.has(jahr)) jahr = logic.aktuellesAusbildungsjahr(user);

    const heute = D.today().toDateString();
    const faecher = db.all('SELECT * FROM faecher WHERE azubi_id = ? AND ausbildungsjahr = ? ORDER BY name COLLATE NOCASE', user.id, jahr);
    const kommend = db.all('SELECT * FROM kalender_eintraege WHERE azubi_id = ? AND ausbildungsjahr = ? AND datum >= ? ORDER BY datum', user.id, jahr, heute);
    const vergangen = db.all('SELECT * FROM kalender_eintraege WHERE azubi_id = ? AND ausbildungsjahr = ? AND datum < ? ORDER BY datum DESC', user.id, jahr, heute);

    const namen = new Set(faecher.map(f => f.name));
    const notenProFach = db.all('SELECT * FROM kalender_eintraege WHERE azubi_id = ? AND ausbildungsjahr = ? AND note IS NOT NULL', user.id, jahr)
      .filter(e => namen.has(e.fach))
      .groupBy('fach')
      .map(eintraege => ({ eintraege, durchschnitt: Math.round(eintraege.avg('note') * 100) / 100 }));
    const anzahlNoten = notenProFach.sum(f => f.eintraege.length);
    const gesamtdurchschnitt = anzahlNoten ? Math.round(notenProFach.flatMap(f => f.eintraege).avg('note') * 100) / 100 : null;
    const stats = {
      gesamtdurchschnitt,
      anzahlFaecher: faecher.length,
      anzahlNoten,
      aktuellesJahr: logic.aktuellesAusbildungsjahr(user),
      anzahlJahre: logic.anzahlAusbildungsjahre(user),
      wochenGesamt: logic.ausbildungswochenGesamt(user),
      wochenAbgeschlossen: logic.ausbildungswochenAbgeschlossen(user),
    };
    res.page('kalender/index', { title: 'Noten & Termine', faecher, kommend, vergangen, notenProFach, stats, jahre, jahr });
  });

  app.post('/kalender', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, { ...regeln, ausbildungsjahr: 'nullable|integer|min:1' });
    if (!ok) return res.withErrors(errors);
    db.insert('kalender_eintraege', {
      azubi_id: req.user.id, ausbildungsjahr: gueltigesJahr(req.user, data.ausbildungsjahr),
      fach: data.fach, typ: data.typ, datum: data.datum, uhrzeit: data.uhrzeit, thema: data.thema, lernnotizen: data.lernnotizen, note: data.note,
    });
    res.flash('success', 'Eintrag erstellt.').back();
  });

  // Faecher (vor /kalender/:id)
  app.post('/kalender/fach', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, { name: 'required|string|max:255', beschreibung: 'nullable|string|max:255', ausbildungsjahr: 'nullable|integer|min:1' });
    if (!ok) return req.wantsJson() ? res.status(422).json({ errors, message: Object.values(errors)[0] }) : res.withErrors(errors);
    const jahr = gueltigesJahr(req.user, data.ausbildungsjahr);
    let fach = db.get('SELECT * FROM faecher WHERE azubi_id = ? AND ausbildungsjahr = ? AND name = ?', req.user.id, jahr, data.name);
    if (!fach) {
      const id = db.insert('faecher', { azubi_id: req.user.id, ausbildungsjahr: jahr, name: data.name, beschreibung: data.beschreibung });
      fach = db.get('SELECT * FROM faecher WHERE id = ?', id);
    }
    if (req.wantsJson()) return res.json({ id: fach.id, name: fach.name, beschreibung: fach.beschreibung });
    res.flash('success', 'Fach hinzugefügt.').back();
  });
  app.delete('/kalender/fach/:id', auth, (req, res) => {
    const f = orFail(db.get('SELECT * FROM faecher WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
    db.run('DELETE FROM faecher WHERE id = ?', f.id);
    if (req.wantsJson()) return res.json({ success: true });
    res.flash('success', 'Fach gelöscht.').back();
  });

  app.patch('/kalender/:id', auth, (req, res) => {
    const e = orFail(db.get('SELECT * FROM kalender_eintraege WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
    const { ok, errors, data } = validate(req.body, regeln);
    if (!ok) return res.withErrors(errors);
    db.update('kalender_eintraege', e.id, data);
    res.flash('success', 'Eintrag aktualisiert.').back();
  });
  app.delete('/kalender/:id', auth, (req, res) => {
    const e = orFail(db.get('SELECT * FROM kalender_eintraege WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
    db.run('DELETE FROM kalender_eintraege WHERE id = ?', e.id);
    res.flash('success', 'Eintrag gelöscht.').back();
  });

  void collect;
};
