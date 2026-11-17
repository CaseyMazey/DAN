'use strict';
// Minimaler Validator mit den Laravel-Regeln, die DAN tatsaechlich nutzt.
// Fehlermeldungen auf Deutsch. Rueckgabe: { errors, data } - errors ist leer,
// wenn alles passt.

const { D } = require('./dates');

const NAMEN = {
  vorname: 'Vorname', nachname: 'Nachname', email: 'E-Mail', password: 'Passwort', current_password: 'Aktuelles Passwort',
  ausbildungsbeginn: 'Ausbildungsbeginn', ausbildungsende: 'Ausbildungsende', abteilung: 'Abteilung',
  titel: 'Titel', beschreibung: 'Beschreibung', kategorie: 'Kategorie', stunden: 'Stunden',
  stunden_abziehen_bei: 'Stunden abziehen bei', status: 'Status', max_stunden: 'Max. Stunden',
  betrieb_stunden: 'Betriebsstunden', berufsschule_stunden: 'Berufsschulstunden',
  fach: 'Fach', typ: 'Typ', datum: 'Datum', uhrzeit: 'Uhrzeit', thema: 'Thema', lernnotizen: 'Lernnotizen', note: 'Note',
  name: 'Name', von_datum: 'Von', bis_datum: 'Bis', bezeichnung: 'Bezeichnung', bearbeitungs_kommentar: 'Kommentar',
};
const n = (f) => NAMEN[f] || f;
const leer = (v) => v === undefined || v === null || (typeof v === 'string' && v.trim() === '');
const istDatum = (v) => /^\d{4}-\d{2}-\d{2}/.test(String(v)) && D.parse(v) !== null;

/**
 * rules: { feld: 'required|string|max:255' | [..., (value, data) => fehlertext|null] }
 */
function validate(body, rules) {
  const errors = {};
  const data = {};
  for (const [feld, def] of Object.entries(rules)) {
    const liste = Array.isArray(def) ? def : def.split('|');
    let v = body[feld];
    if (typeof v === 'string') v = v.trim();
    const nullable = liste.includes('nullable');
    const fehler = (msg) => { if (!errors[feld]) errors[feld] = msg; };

    if (leer(v)) {
      const reqIf = liste.find(r => typeof r === 'string' && r.startsWith('required_if:'));
      if (liste.includes('required')) fehler(`${n(feld)} ist erforderlich.`);
      else if (reqIf) {
        const [anderes, wert] = reqIf.slice(12).split(',');
        if (String(body[anderes]) === wert) fehler(`${n(feld)} ist erforderlich.`);
      }
      data[feld] = null;
      continue;
    }
    data[feld] = v;
    for (const r of liste) {
      if (typeof r === 'function') { const m = r(v, body); if (m) fehler(m); continue; }
      const [name, arg] = r.split(':');
      switch (name) {
        case 'required': case 'nullable': case 'string': case 'required_if': break;
        case 'email': if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) fehler(`${n(feld)} muss eine gültige E-Mail-Adresse sein.`); break;
        case 'numeric': if (isNaN(Number(String(v).replace(',', '.')))) fehler(`${n(feld)} muss eine Zahl sein.`); else data[feld] = Number(String(v).replace(',', '.')); break;
        case 'integer': if (!/^-?\d+$/.test(String(v))) fehler(`${n(feld)} muss eine ganze Zahl sein.`); else data[feld] = parseInt(v, 10); break;
        case 'date': if (!istDatum(v)) fehler(`${n(feld)} ist kein gültiges Datum.`); break;
        case 'date_format': if (!/^\d{2}:\d{2}$/.test(v)) fehler(`${n(feld)} muss im Format HH:MM sein.`); break;
        case 'in': if (!arg.split(',').includes(String(v))) fehler(`${n(feld)} ist ungültig.`); break;
        case 'confirmed': if (v !== body[`${feld}_confirmation`]) fehler(`${n(feld)} stimmt nicht mit der Bestätigung überein.`); break;
        case 'min':
          if (liste.includes('numeric') || liste.includes('integer')) { if (Number(data[feld]) < Number(arg)) fehler(`${n(feld)} muss mindestens ${arg} sein.`); }
          else if (String(v).length < Number(arg)) fehler(`${n(feld)} muss mindestens ${arg} Zeichen lang sein.`);
          break;
        case 'max':
          if (liste.includes('numeric') || liste.includes('integer')) { if (Number(data[feld]) > Number(arg)) fehler(`${n(feld)} darf höchstens ${arg} sein.`); }
          else if (String(v).length > Number(arg)) fehler(`${n(feld)} darf höchstens ${arg} Zeichen lang sein.`);
          break;
        case 'after': { const o = body[arg]; if (!leer(o) && istDatum(o) && istDatum(v) && !D.parse(v).gt(D.parse(o))) fehler(`${n(feld)} muss nach ${n(arg)} liegen.`); break; }
        case 'after_or_equal': { const o = body[arg]; if (!leer(o) && istDatum(o) && istDatum(v) && D.parse(v).lt(D.parse(o))) fehler(`${n(feld)} muss gleich oder nach ${n(arg)} liegen.`); break; }
        default: throw new Error(`Unbekannte Regel: ${name}`);
      }
    }
  }
  return { errors, data, ok: Object.keys(errors).length === 0 };
}

/** Verbietet krumme Stundenwerte (nur Schritte von 0,25). */
const viertelstunde = (v) => (Math.abs(Number(String(v).replace(',', '.')) * 4 % 1) > 1e-9 ? 'Die Stunden müssen in Schritten von 0,25 angegeben werden.' : null);

module.exports = { validate, viertelstunde };
