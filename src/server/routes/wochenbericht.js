'use strict';
const { D } = require('../dates');
const { collect } = require('../collection');
const { validate, viertelstunde } = require('../validate');
const { orFail } = require('../app-helpers');
const pdf = require('../pdf');

module.exports = (app, ctx, { auth, named }) => {
  const { db, logic } = ctx;
  const bericht = (req, id) => orFail(db.get('SELECT * FROM wochenberichte WHERE id = ? AND azubi_id = ?', id, req.user.id));
  const eintraegeVon = (id) => db.all('SELECT * FROM wochenbericht_eintraege WHERE wochenbericht_id = ? ORDER BY id', id);
  // Es gibt nur ein Konto - Autor einer Bearbeitung ist immer der Nutzer selbst.
  const bearbeitungenVon = (id, user) => db.all('SELECT * FROM wochenbericht_bearbeitungen WHERE wochenbericht_id = ? ORDER BY id', id)
    .map(b => ({ ...b, autor: user }));
  const offeneAufgaben = (uid) => db.all(logic.OFFENE_AUFGABEN, uid);

  // ── Uebersicht ────────────────────────────────────────────────────────
  app.get('/wochenbericht', auth, named('wochenbericht.index'), (req, res) => {
    const user = req.user;
    if (user.ausbildungsbeginn) logic.lueckenscan(db, user);
    const startDerWoche = logic.wocheStart();

    const mitDetails = (b) => b && { ...b, eintraege: eintraegeVon(b.id), bearbeitungen: bearbeitungenVon(b.id, user) };
    const aktuell = mitDetails(db.get('SELECT * FROM wochenberichte WHERE azubi_id = ? AND startdatum = ?', user.id, startDerWoche));
    let vorschau = aktuell;
    if (req.query.vorschau) vorschau = mitDetails(bericht(req, req.query.vorschau));

    let aufgabenVorschau = offeneAufgaben(user.id).groupBy(a => (['betrieb', 'unterweisung', 'berufsschule'].includes(a.kategorie) ? a.kategorie : 'betrieb'));
    if (vorschau?.id !== aktuell?.id) aufgabenVorschau = collect().groupBy('x');

    let zeitraum = null, zeitraumVoll = null, zeitraumTage = null, ausbildungsjahr = null;
    if (vorschau) {
      zeitraum = logic.zeitraumFuerWoche(db, user.id, vorschau.startdatum, vorschau.enddatum);
      zeitraumVoll = !!zeitraum && logic.deckWocheVollstaendigAb(zeitraum, vorschau.startdatum, vorschau.enddatum);
      zeitraumTage = zeitraum && !zeitraumVoll ? logic.betroffeneTageKurz(zeitraum, vorschau.startdatum, vorschau.enddatum) : null;
      ausbildungsjahr = logic.ausbildungsjahrFuer(user, vorschau.startdatum);
    }

    const alle = db.all('SELECT abgegeben, azubi_signiert, bearbeitet, startdatum FROM wochenberichte WHERE azubi_id = ?', user.id);
    const stats = {
      gesamt: alle.length,
      signiert: alle.filter(b => b.azubi_signiert).length,
      ausstehend: alle.filter(b => b.abgegeben && !b.azubi_signiert).length,
      bearbeitet: alle.filter(b => b.bearbeitet).length,
    };

    let vergangen = db.all('SELECT * FROM wochenberichte WHERE azubi_id = ? AND startdatum != ? ORDER BY startdatum DESC', user.id, startDerWoche);
    if (req.query.jahr) vergangen = vergangen.filter(b => b.startdatum.year === Number(req.query.jahr));
    if (req.query.monat) vergangen = vergangen.filter(b => b.startdatum.month === Number(req.query.monat));
    if (req.query.nur_signierte === '1') vergangen = vergangen.filter(b => b.azubi_signiert);
    if (req.query.kw) vergangen = vergangen.filter(b => b.startdatum.isoWeek === Number(req.query.kw));

    const jahre = collect([...new Set(alle.map(b => b.startdatum.year))]).sortDesc();
    res.page('wochenbericht/index', { title: 'Wochenberichte', aktuell, vorschau, vergangen, jahre, aufgabenVorschau, zeitraum, zeitraumVoll, zeitraumTage, stats, ausbildungsjahr });
  });

  // ── Detail ────────────────────────────────────────────────────────────
  const zeige = (req, res, b) => {
    const alleEintraege = eintraegeVon(b.id);
    b.gesamtstunden = () => alleEintraege.sum('stunden');
    const eintraege = alleEintraege.sortBy('kategorie').groupBy('kategorie');
    const kommentare = db.all('SELECT * FROM wochenbericht_kommentare WHERE wochenbericht_id = ? ORDER BY id', b.id).map(k => ({ ...k, autor: req.user }));
    const bearbeitungen = bearbeitungenVon(b.id, req.user);
    const zeitraum = logic.zeitraumFuerWoche(db, b.azubi_id, b.startdatum, b.enddatum);
    const zeitraumVoll = !!zeitraum && logic.deckWocheVollstaendigAb(zeitraum, b.startdatum, b.enddatum);
    const zeitraumTage = zeitraum && !zeitraumVoll ? logic.betroffeneTageKurz(zeitraum, b.startdatum, b.enddatum) : null;
    res.page('wochenbericht/show', { title: `KW ${b.startdatum.format('W / Y')}`, bericht: b, eintraege, kommentare, bearbeitungen, zeitraum, zeitraumVoll, zeitraumTage }, { routeName: 'wochenbericht.show' });
  };
  app.get('/wochenbericht/aktuell', auth, (req, res) => zeige(req, res, logic.berichtFirstOrCreate(db, req.user.id, logic.wocheStart(), logic.wocheEnde())));

  // ── Zeitraeume (vor /:id, sonst wuerde "zeitraeume" als ID gelesen) ────
  const zeitraumRegeln = (req, ausserId = null) => ({
    von_datum: ['required', 'date', (v, body) => {
      if (!body.bis_datum) return null;
      const ueber = db.get('SELECT id FROM zeitraeume WHERE azubi_id = ? AND id != ? AND von_datum <= ? AND bis_datum >= ?', req.user.id, ausserId ?? -1, body.bis_datum, v);
      return ueber ? 'Dieser Zeitraum überschneidet sich mit einem bereits vorhandenen Zeitraum.' : null;
    }],
    bis_datum: 'required|date|after_or_equal:von_datum',
    bezeichnung: 'required|string|max:255',
  });
  app.get('/wochenbericht/zeitraeume', auth, named('wochenbericht.zeitraeume'), (req, res) => {
    const zeitraeume = db.all('SELECT * FROM zeitraeume WHERE azubi_id = ? ORDER BY von_datum DESC', req.user.id);
    res.page('wochenbericht/zeitraeume', { title: 'Zeiträume', zeitraeume });
  });
  app.post('/wochenbericht/zeitraeume', auth, (req, res) => {
    const { ok, errors, data } = validate(req.body, zeitraumRegeln(req));
    if (!ok) return res.withErrors(errors);
    db.insert('zeitraeume', { azubi_id: req.user.id, von_datum: data.von_datum, bis_datum: data.bis_datum, bezeichnung: data.bezeichnung });
    res.flash('success', 'Zeitraum erstellt.').back();
  });
  app.put('/wochenbericht/zeitraeume/:id', auth, (req, res) => {
    const z = orFail(db.get('SELECT * FROM zeitraeume WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
    const { ok, errors, data } = validate(req.body, zeitraumRegeln(req, z.id));
    if (!ok) return res.withErrors(errors);
    db.update('zeitraeume', z.id, { von_datum: data.von_datum, bis_datum: data.bis_datum, bezeichnung: data.bezeichnung });
    res.flash('success', 'Zeitraum aktualisiert.').back();
  });
  app.delete('/wochenbericht/zeitraeume/:id', auth, (req, res) => {
    const z = orFail(db.get('SELECT * FROM zeitraeume WHERE id = ? AND azubi_id = ?', req.params.id, req.user.id));
    db.run('DELETE FROM zeitraeume WHERE id = ?', z.id);
    res.flash('success', 'Zeitraum gelöscht.').back();
  });

  app.post('/wochenbericht/fehlend-erstellen', auth, (req, res) => {
    if (!req.user.ausbildungsbeginn) return res.flash('error', 'Kein Ausbildungsbeginn hinterlegt.').back();
    logic.lueckenscan(db, req.user);
    res.flash('success', 'Fehlende Berichte wurden erstellt.').back();
  });

  // ── Sammel-PDF ────────────────────────────────────────────────────────
  app.post('/wochenbericht/sammel-pdf', auth, async (req, res, next) => {
    try {
      const user = req.user;
      const ids = String(req.body.ids || '').split(',').map(Number).filter(Boolean);
      const berichte = ids.length
        ? db.all(`SELECT * FROM wochenberichte WHERE azubi_id = ? AND id IN (${ids.map(() => '?').join(',')}) ORDER BY startdatum`, user.id, ...ids)
        : collect();
      if (berichte.isEmpty()) return res.flash('error', 'Keine Berichte ausgewählt.').back();
      const { maxBetrieb, maxBerufsschule } = logic.maxWerte(db, user.id);
      const seiten = berichte.map(b => {
        const [eintraege, stunden] = logic.ausGespeichertenEintraegen(db, b, maxBetrieb, maxBerufsschule);
        return { bericht: b, eintraege, stunden, ausbildungsjahr: logic.ausbildungsjahrFuer(user, b.startdatum) ?? '?' };
      });
      const daten = await pdf.sammelPdf(user, seiten, ctx.dataDir);
      sendePdf(res, daten, `Sammel_Wochenberichte_${user.nachname}_${D.now().format('Y-m-d')}.pdf`);
    } catch (e) { next(e); }
  });

  // ── Einzelbericht ─────────────────────────────────────────────────────
  app.get('/wochenbericht/:id(\\d+)', auth, (req, res) => zeige(req, res, bericht(req, req.params.id)));

  app.post('/wochenbericht/:id/speichern', auth, (req, res) => {
    const b = bericht(req, req.params.id);
    if (b.gesperrt) return res.flash('error', 'Dieser Bericht ist gesperrt.').back();
    const regeln = {
      kategorie: 'required|in:betrieb,unterweisung,berufsschule',
      beschreibung: 'required|string|max:2000',
      stunden: ['required', 'numeric', 'min:0', 'max:99', viertelstunde],
    };
    if (b.abgegeben) regeln.bearbeitungs_kommentar = 'required|string|max:500';
    const { ok, errors, data } = validate(req.body, regeln);
    if (!ok) return res.withErrors(errors);
    db.transaction(() => {
      db.insert('wochenbericht_eintraege', { wochenbericht_id: b.id, kategorie: data.kategorie, beschreibung: data.beschreibung, stunden: data.stunden });
      if (b.abgegeben) {
        db.update('wochenberichte', b.id, { bearbeitet: true });
        db.insert('wochenbericht_bearbeitungen', { wochenbericht_id: b.id, autor_id: req.user.id, kommentar: data.bearbeitungs_kommentar });
      }
    });
    res.flash('success', 'Eintrag gespeichert.');
    const config = logic.stundenConfig(db, req.user.id);
    if (config) {
      const summe = db.value('SELECT COALESCE(SUM(stunden),0) FROM wochenbericht_eintraege WHERE wochenbericht_id = ?', b.id);
      if (summe > config.max_stunden * 1.15) {
        res.flash('warning', `Die Wochensumme (${summe} Std.) liegt deutlich über deiner konfigurierten Sollstundenzahl (${config.max_stunden} Std.) - bitte kurz prüfen.`);
      }
    }
    res.back();
  });

  app.post('/wochenbericht/:id/bearbeitung', auth, (req, res) => {
    const b = bericht(req, req.params.id);
    if (b.gesperrt) return res.flash('error', 'Dieser Bericht ist gesperrt.').back();
    const { ok, errors, data } = validate(req.body, { bearbeitungs_kommentar: 'required|string|max:500' });
    if (!ok) return res.withErrors(errors);
    db.update('wochenberichte', b.id, { bearbeitet: true });
    db.insert('wochenbericht_bearbeitungen', { wochenbericht_id: b.id, autor_id: req.user.id, kommentar: data.bearbeitungs_kommentar });
    res.flash('success', 'Kommentar gespeichert.').back();
  });

  app.post('/wochenbericht/:id/abgeben', auth, (req, res) => {
    const b = bericht(req, req.params.id);
    if (b.gesperrt) return res.flash('error', 'Dieser Bericht ist bereits gesperrt.').back();
    logic.submitOffeneAufgaben(db, b, false);
    res.flash('success', 'Bericht erfolgreich abgegeben.').back();
  });

  app.post('/wochenbericht/:id/signieren', auth, (req, res) => {
    const b = bericht(req, req.params.id);
    db.update('wochenberichte', b.id, { azubi_signiert: true, azubi_signiert_am: D.now() });
    res.flash('success', 'Bericht signiert.').back();
  });

  app.delete('/wochenbericht/:id/signatur', auth, (req, res) => {
    const b = bericht(req, req.params.id);
    db.update('wochenberichte', b.id, { azubi_signiert: false, azubi_signiert_am: null });
    res.flash('success', 'Signatur entfernt.').back();
  });

  app.get('/wochenbericht/:id/pdf', auth, async (req, res, next) => {
    try {
      const user = req.user;
      const b = bericht(req, req.params.id);
      const { maxBetrieb, maxBerufsschule } = logic.maxWerte(db, user.id);
      const istAktuelleWoche = b.startdatum.toDateString() === logic.wocheStart();
      const [eintraege, stunden] = !b.abgegeben && istAktuelleWoche
        ? logic.ausLiveAufgaben(db, offeneAufgaben(user.id), b, maxBetrieb, maxBerufsschule)
        : logic.ausGespeichertenEintraegen(db, b, maxBetrieb, maxBerufsschule);
      const jahr = logic.ausbildungsjahrFuer(user, b.startdatum) ?? '?';
      const daten = await pdf.ihkPdf([{ bericht: b, eintraege, stunden, ausbildungsjahr: jahr }], user, ctx.dataDir);
      sendePdf(res, daten, `Wochenbericht_${b.startdatum.format('o')}_KW${b.startdatum.format('W')}_${user.nachname}, ${user.vorname}.pdf`);
    } catch (e) { next(e); }
  });
};

function sendePdf(res, daten, dateiname) {
  res.set({
    'Content-Type': 'application/pdf',
    'Content-Disposition': `attachment; filename="${dateiname.replace(/[^\w ,.\-äöüÄÖÜß]/g, '_')}"; filename*=UTF-8''${encodeURIComponent(dateiname)}`,
  });
  res.send(Buffer.from(daten));
}
