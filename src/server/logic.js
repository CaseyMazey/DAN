'use strict';
// Fachlogik, 1:1 aus der Laravel-Version uebertragen (User-Model, Zeitraum-
// Model, LueckenscanService, WochenberichtSnapshotService, EintraegeService).

const { D } = require('./dates');
const { C, collect } = require('./collection');

// ── User ────────────────────────────────────────────────────────────────
function aktuellesAusbildungsjahr(user) {
  if (!user.ausbildungsbeginn) return 1;
  return Math.max(1, Math.floor(user.ausbildungsbeginn.diffInMonths(D.now()) / 12) + 1);
}
function anzahlAusbildungsjahre(user) {
  if (!user.ausbildungsbeginn || !user.ausbildungsende) return aktuellesAusbildungsjahr(user);
  const jahre = Math.ceil(user.ausbildungsbeginn.diffInMonths(user.ausbildungsende) / 12);
  return Math.max(aktuellesAusbildungsjahr(user), jahre);
}
function ausbildungswochenGesamt(user) {
  if (!user.ausbildungsbeginn || !user.ausbildungsende) return null;
  return Math.max(1, Math.floor(user.ausbildungsbeginn.diffInWeeks(user.ausbildungsende)));
}
function ausbildungswochenAbgeschlossen(user) {
  if (!user.ausbildungsbeginn) return null;
  const abgeschlossen = Math.max(0, Math.floor(user.ausbildungsbeginn.diffInWeeks(D.now())));
  const gesamt = ausbildungswochenGesamt(user);
  return gesamt !== null ? Math.min(abgeschlossen, gesamt) : abgeschlossen;
}
/** Ausbildungsjahr fuer ein konkretes Berichtsdatum (oder null ohne Beginn). */
function ausbildungsjahrFuer(user, datum) {
  if (!user.ausbildungsbeginn) return null;
  return Math.floor(user.ausbildungsbeginn.diffInMonths(datum) / 12) + 1;
}
function enrichUser(u) {
  if (!u) return u;
  u.full_name = `${u.vorname} ${u.nachname}`;
  return u;
}

// ── Zeitraum ────────────────────────────────────────────────────────────
function zeitraumFuerWoche(db, azubiId, start, ende) {
  return db.get(
    'SELECT * FROM zeitraeume WHERE azubi_id = ? AND von_datum <= ? AND bis_datum >= ? ORDER BY von_datum LIMIT 1',
    azubiId, D.parse(ende).toDateString(), D.parse(start).toDateString());
}
function deckWocheVollstaendigAb(z, start, ende) {
  return z.von_datum.lte(start) && z.bis_datum.gte(ende);
}
function betroffeneTage(z, start, ende) {
  const tage = new C();
  for (let tag = D.parse(start).startOfDay(); tag.lte(ende); tag = tag.addDays(1)) {
    if (tag.between(z.von_datum, z.bis_datum)) tage.push(tag);
  }
  return tage;
}
function betroffeneTageKurz(z, start, ende) {
  const tage = betroffeneTage(z, start, ende);
  if (tage.isEmpty()) return '';
  const kuerzel = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
  const labels = tage.map(t => kuerzel[t.dayOfWeekIso - 1]);
  const zusammenhaengend = tage.length > 1 && Math.round(tage.first().diffInDays(tage.last())) === tage.length - 1;
  return zusammenhaengend ? `${labels.first()}–${labels.last()}` : labels.implode(', ');
}
/** Ueberschreibt eintraege/stunden (in place), falls ein Zeitraum die Woche betrifft. */
function ueberschreibeFuerWoche(db, azubiId, start, ende, eintraege, stunden) {
  const z = zeitraumFuerWoche(db, azubiId, start, ende);
  if (!z) return;
  const kategorien = ['betrieb', 'unterweisung', 'berufsschule'];
  if (deckWocheVollstaendigAb(z, start, ende)) {
    for (const k of kategorien) { eintraege[k] = collect([{ beschreibung: z.bezeichnung }]); stunden[k] = null; }
    return;
  }
  const hinweis = { beschreibung: `${betroffeneTageKurz(z, start, ende)}: ${z.bezeichnung}` };
  for (const k of kategorien) eintraege[k] = collect([hinweis]).concat(eintraege[k] || []);
  const gesamt = Math.round(D.parse(start).diffInDays(ende)) + 1;
  const abgedeckt = betroffeneTage(z, start, ende).length;
  const anteil = gesamt > 0 ? Math.max(0, (gesamt - abgedeckt) / gesamt) : 1;
  for (const k of ['betrieb', 'berufsschule']) {
    if (stunden[k] != null) stunden[k] = Math.round(stunden[k] * anteil * 100) / 100;
  }
}

// ── Wochenberichte ──────────────────────────────────────────────────────
const wocheStart = (d = D.now()) => d.startOfWeek().toDateString();
const wocheEnde = (d = D.now()) => d.endOfWeek().subDays(2).toDateString();

function berichtFirstOrCreate(db, azubiId, startdatum, enddatum) {
  const finde = () => db.get('SELECT * FROM wochenberichte WHERE azubi_id = ? AND startdatum = ?', azubiId, startdatum);
  let b = finde();
  if (!b) {
    db.run('INSERT OR IGNORE INTO wochenberichte (azubi_id, startdatum, enddatum, created_at, updated_at) VALUES (?,?,?,?,?)',
      azubiId, startdatum, enddatum, D.now(), D.now());
    b = finde();
  }
  return b;
}

/** Legt fuer jede Woche seit Ausbildungsbeginn einen leeren Bericht an. */
function lueckenscan(db, user) {
  if (!user.ausbildungsbeginn) return 0;
  const heute = D.now().startOfWeek();
  const vorhanden = new Set(db.all('SELECT startdatum FROM wochenberichte WHERE azubi_id = ?', user.id).map(w => w.startdatum.toDateString()));
  let n = 0;
  db.transaction(() => {
    for (let c = user.ausbildungsbeginn.startOfWeek(); c.lte(heute); c = c.addWeeks(1)) {
      const s = c.toDateString();
      if (vorhanden.has(s)) continue;
      db.run('INSERT OR IGNORE INTO wochenberichte (azubi_id, startdatum, enddatum, created_at, updated_at) VALUES (?,?,?,?,?)',
        user.id, s, c.endOfWeek().subDays(2).toDateString(), D.now(), D.now());
      n++;
    }
  });
  return n;
}

const OFFENE_AUFGABEN = "SELECT * FROM aufgaben WHERE azubi_id = ? AND archiviert = 0 AND status IN ('in_bearbeitung','fertig')";

/**
 * Uebernimmt offene Aufgaben in den Bericht und markiert ihn als abgegeben.
 * angelegtVor: optional - nur Aufgaben beruecksichtigen, die vor diesem
 * Zeitpunkt angelegt wurden (fuer das automatische Abschliessen der Vorwoche).
 */
function submitOffeneAufgaben(db, bericht, automatisch, angelegtVor = null) {
  if (bericht.abgegeben) return;
  db.transaction(() => {
    const z = zeitraumFuerWoche(db, bericht.azubi_id, bericht.startdatum, bericht.enddatum);
    const voll = z && deckWocheVollstaendigAb(z, bericht.startdatum, bericht.enddatum);
    if (!voll) {
      const aufgaben = angelegtVor
        ? db.all(OFFENE_AUFGABEN + ' AND created_at < ?', bericht.azubi_id, angelegtVor)
        : db.all(OFFENE_AUFGABEN, bericht.azubi_id);
      for (const a of aufgaben) {
        db.insert('wochenbericht_eintraege', {
          wochenbericht_id: bericht.id,
          kategorie: a.kategorie,
          beschreibung: a.titel + (a.beschreibung ? `: ${a.beschreibung}` : ''),
          stunden: a.stunden,
          stunden_abziehen_bei: a.stunden_abziehen_bei,
        });
        if (a.status === 'fertig') db.update('aufgaben', a.id, { in_wochenbericht: true, archiviert: true, archiviert_am: D.now() });
        else db.update('aufgaben', a.id, { in_wochenbericht: true });
      }
    }
    db.update('wochenberichte', bericht.id, { abgegeben: true, abgegeben_am: D.now(), auto_abgeschickt: automatisch });
  });
}

/**
 * Ersetzt den frueheren Freitags-Cron: Beim Oeffnen der App wird die Vorwoche
 * automatisch abgegeben, falls noch offen. Beruecksichtigt nur Aufgaben, die
 * schon vor dieser Woche existierten - was erst diese Woche angelegt wurde,
 * gehoert nicht in den Bericht der Vorwoche.
 */
function schliesseVorwocheAbFallsNoetig(db, user) {
  const vor = D.now().subWeek();
  const dieseWoche = D.now().startOfWeek().toDateTimeString();
  if (!db.get(OFFENE_AUFGABEN + ' AND created_at < ? LIMIT 1', user.id, dieseWoche)) return;
  const bericht = berichtFirstOrCreate(db, user.id, wocheStart(vor), wocheEnde(vor));
  submitOffeneAufgaben(db, bericht, true, dieseWoche);
}

function stundenConfig(db, userId) {
  return db.get('SELECT * FROM stunden_config WHERE azubi_id = ?', userId);
}
function maxWerte(db, userId) {
  const c = stundenConfig(db, userId);
  return { maxBetrieb: c ? c.betrieb_stunden : 32, maxBerufsschule: c ? c.berufsschule_stunden : 10 };
}

/** eintraege/stunden aus den gespeicherten Eintraegen eines Berichts. */
function ausGespeichertenEintraegen(db, bericht, maxBetrieb, maxBerufsschule) {
  const alle = db.all('SELECT * FROM wochenbericht_eintraege WHERE wochenbericht_id = ? ORDER BY id', bericht.id);
  return baueEintraege(db, bericht, alle, maxBetrieb, maxBerufsschule);
}
/** eintraege/stunden aus den live offenen Aufgaben (Vorschau laufende Woche). */
function ausLiveAufgaben(db, aufgaben, bericht, maxBetrieb, maxBerufsschule) {
  const gemappt = aufgaben.map(a => ({
    kategorie: a.kategorie,
    beschreibung: a.titel + (a.beschreibung ? `: ${a.beschreibung}` : ''),
    stunden: a.stunden,
    stunden_abziehen_bei: a.stunden_abziehen_bei,
  }));
  return baueEintraege(db, bericht, gemappt, maxBetrieb, maxBerufsschule);
}
function baueEintraege(db, bericht, alle, maxBetrieb, maxBerufsschule) {
  const kat = k => alle.filter(e => e.kategorie === k);
  const eintraege = { betrieb: kat('betrieb'), unterweisung: kat('unterweisung'), berufsschule: kat('berufsschule') };
  const abzug = ziel => alle.filter(e => e.kategorie === 'unterweisung' && e.stunden_abziehen_bei === ziel).sum('stunden');
  const stunden = {
    betrieb: Math.max(0, maxBetrieb - abzug('betrieb')),
    unterweisung: kat('unterweisung').sum('stunden'),
    berufsschule: Math.max(0, maxBerufsschule - abzug('berufsschule')),
  };
  ueberschreibeFuerWoche(db, bericht.azubi_id, bericht.startdatum, bericht.enddatum, eintraege, stunden);
  return [eintraege, stunden];
}

module.exports = {
  aktuellesAusbildungsjahr, anzahlAusbildungsjahre, ausbildungswochenGesamt, ausbildungswochenAbgeschlossen,
  ausbildungsjahrFuer, enrichUser,
  zeitraumFuerWoche, deckWocheVollstaendigAb, betroffeneTage, betroffeneTageKurz, ueberschreibeFuerWoche,
  wocheStart, wocheEnde, berichtFirstOrCreate, lueckenscan, submitOffeneAufgaben, schliesseVorwocheAbFallsNoetig,
  stundenConfig, maxWerte, ausGespeichertenEintraegen, ausLiveAufgaben, OFFENE_AUFGABEN,
};
