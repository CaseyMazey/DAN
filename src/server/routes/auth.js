'use strict';
// Ersteinrichtung: Beim allerersten Start legt man Name und Ausbildungszeitraum
// fest. Eine Anmeldung gibt es nicht - DAN ist eine Einzelplatz-App.

const { validate } = require('../validate');

module.exports = (app, { db }, { guest }) => {
  app.get('/einrichten', guest, (req, res) => {
    res.page('auth/setup', { title: 'Einrichten' }, { layout: 'layouts/guest' });
  });

  app.post('/einrichten', guest, (req, res) => {
    const { ok, errors, data } = validate(req.body, {
      vorname: 'required|string|max:255',
      nachname: 'required|string|max:255',
      ausbildungsbeginn: 'required|date',
      ausbildungsende: 'nullable|date|after:ausbildungsbeginn',
      abteilung: 'nullable|string|max:255',
    });
    if (!ok) return res.withErrors(errors);
    db.insert('users', {
      vorname: data.vorname,
      nachname: data.nachname,
      email: '',      // Spalten bleiben fuer den DAN2-Import bestehen, werden aber nicht mehr benutzt
      password: '',
      ausbildungsbeginn: data.ausbildungsbeginn,
      ausbildungsende: data.ausbildungsende,
      abteilung: data.abteilung,
    });
    res.flash('success', 'Willkommen bei DAN!').redirect('/dashboard');
  });

  // Alte Adressen aus der Version mit Login
  app.get('/login', (req, res) => res.redirect('/'));
};
