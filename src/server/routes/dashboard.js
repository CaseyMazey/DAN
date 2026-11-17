'use strict';
const { D } = require('../dates');

module.exports = (app, { db, logic }, { auth, named }) => {
  app.get('/dashboard', auth, named('dashboard'), (req, res) => {
    const user = req.user;
    // Sicherheitsnetz: offene Vorwoche automatisch abgeben (statt Freitags-Cron).
    logic.schliesseVorwocheAbFallsNoetig(db, user);

    const zaehle = (status) => db.value('SELECT COUNT(*) FROM aufgaben WHERE azubi_id = ? AND archiviert = 0 AND status = ?', user.id, status);
    const aufgabenStats = { geplant: zaehle('geplant'), in_bearbeitung: zaehle('in_bearbeitung'), fertig: zaehle('fertig') };
    const stundenDieseWoche = db.value("SELECT COALESCE(SUM(stunden),0) FROM aufgaben WHERE azubi_id = ? AND archiviert = 0 AND status IN ('in_bearbeitung','fertig')", user.id);
    const naechsteTermine = db.all('SELECT * FROM kalender_eintraege WHERE azubi_id = ? AND datum >= ? ORDER BY datum LIMIT 5', user.id, D.today().toDateString());
    const nichtSigniert = db.value('SELECT COUNT(*) FROM wochenberichte WHERE azubi_id = ? AND abgegeben = 1 AND azubi_signiert = 0', user.id);
    const berichtStats = {
      gesamt: db.value('SELECT COUNT(*) FROM wochenberichte WHERE azubi_id = ?', user.id),
      signiert: db.value('SELECT COUNT(*) FROM wochenberichte WHERE azubi_id = ? AND azubi_signiert = 1', user.id),
      ausstehend: nichtSigniert,
      bearbeitet: db.value('SELECT COUNT(*) FROM wochenberichte WHERE azubi_id = ? AND bearbeitet = 1', user.id),
    };
    res.page('dashboard', { title: 'Dashboard', aufgabenStats, stundenDieseWoche, naechsteTermine, nichtSigniert, berichtStats });
  });
};
