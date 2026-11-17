'use strict';
// Kleine, abhaengigkeitsfreie Datumsklasse nach dem Vorbild von Carbon (PHP),
// damit Logik und Vorlagen aus der Laravel-Version 1:1 uebertragbar bleiben.
// Alle Werte sind lokale Zeit (die App laeuft nur auf diesem Rechner).

const TAGE = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
const pad = (n, l = 2) => String(n).padStart(l, '0');
const MS_TAG = 86400000;

class D {
  constructor(date) {
    this.d = new Date(date.getTime());
  }

  static now() { return new D(new Date()); }
  static today() { return D.now().startOfDay(); }

  /** Akzeptiert 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM[:SS]', ISO-Strings, Date, D. */
  static parse(v) {
    if (v == null || v === '') return null;
    if (v instanceof D) return v.copy();
    if (v instanceof Date) return new D(v);
    const s = String(v).trim();
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
    if (m && !/[zZ]|[+-]\d{2}:?\d{2}$/.test(s)) {
      return new D(new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0)));
    }
    const d = new Date(s);
    return isNaN(d) ? null : new D(d);
  }

  copy() { return new D(this.d); }
  get year() { return this.d.getFullYear(); }
  get month() { return this.d.getMonth() + 1; }
  get day() { return this.d.getDate(); }
  get dayOfWeekIso() { return this.d.getDay() || 7; }
  get isoWeek() { return isoWeekYear(this.d)[1]; }
  get isoWeekYear() { return isoWeekYear(this.d)[0]; }
  get timestamp() { return Math.floor(this.d.getTime() / 1000); }

  startOfDay() { const c = this.copy(); c.d.setHours(0, 0, 0, 0); return c; }
  /** Montag 00:00 der Woche (wie Carbon::startOfWeek). */
  startOfWeek() { return this.startOfDay().addDays(1 - this.dayOfWeekIso); }
  /** Sonntag der Woche (Datumsteil reicht fuer alle Aufrufer). */
  endOfWeek() { return this.startOfWeek().addDays(6); }

  addDays(n) { const c = this.copy(); c.d.setDate(c.d.getDate() + n); return c; }
  subDays(n) { return this.addDays(-n); }
  addWeeks(n) { return this.addDays(7 * n); }
  subWeek() { return this.addDays(-7); }
  addMonths(n) {
    const c = this.copy(); const tag = c.d.getDate();
    c.d.setDate(1); c.d.setMonth(c.d.getMonth() + n);
    c.d.setDate(Math.min(tag, daysInMonth(c.d.getFullYear(), c.d.getMonth())));
    return c;
  }
  addYears(n) { return this.addMonths(12 * n); }

  cmp(o) { o = D.parse(o); return this.d - o.d; }
  lt(o) { return this.cmp(o) < 0; }
  lte(o) { return this.cmp(o) <= 0; }
  gt(o) { return this.cmp(o) > 0; }
  gte(o) { return this.cmp(o) >= 0; }
  eq(o) { return this.cmp(o) === 0; }
  between(a, b) { return this.gte(a) && this.lte(b); }
  isToday() { return this.toDateString() === D.today().toDateString(); }
  isPast() { return this.d < new Date(); }
  isFuture() { return this.d > new Date(); }
  isFriday() { return this.d.getDay() === 5; }

  /** Vorzeichenbehaftet wie Carbon 3: positiv, wenn o nach this liegt. */
  diffInDays(o) {
    o = D.parse(o);
    const a = Date.UTC(this.year, this.month - 1, this.day);
    const b = Date.UTC(o.year, o.month - 1, o.day);
    return (b - a) / MS_TAG;
  }
  diffInWeeks(o) { return this.diffInDays(o) / 7; }
  /** Monate inkl. Bruchteil (Carbon 3 liefert ebenfalls Fliesskommawerte). */
  diffInMonths(o) {
    o = D.parse(o);
    const sign = o.d >= this.d ? 1 : -1;
    const [a, b] = sign > 0 ? [this, o] : [o, this];
    let ganze = (b.year - a.year) * 12 + (b.month - a.month);
    if (a.addMonths(ganze).gt(b)) ganze--;
    const basis = a.addMonths(ganze);
    const naechste = a.addMonths(ganze + 1);
    const bruch = (b.d - basis.d) / (naechste.d - basis.d);
    return sign * (ganze + bruch);
  }

  toDateString() { return `${this.year}-${pad(this.month)}-${pad(this.day)}`; }
  toDateTimeString() { return `${this.toDateString()} ${pad(this.d.getHours())}:${pad(this.d.getMinutes())}:${pad(this.d.getSeconds())}`; }
  toString() { return this.toDateTimeString(); }
  toJSON() { return this.toDateTimeString(); }
  valueOf() { return this.d.getTime(); }

  /** Untermenge der PHP-date()-Formatzeichen; Wochentag/Monat auf Deutsch. */
  format(f) {
    const d = this.d;
    const [wy, w] = isoWeekYear(d);
    const map = {
      d: () => pad(d.getDate()), j: () => String(d.getDate()),
      m: () => pad(d.getMonth() + 1), n: () => String(d.getMonth() + 1),
      Y: () => String(d.getFullYear()), y: () => pad(d.getFullYear() % 100),
      H: () => pad(d.getHours()), G: () => String(d.getHours()), i: () => pad(d.getMinutes()), s: () => pad(d.getSeconds()),
      W: () => pad(w), o: () => String(wy), N: () => String(d.getDay() || 7),
      l: () => TAGE[d.getDay()], D: () => TAGE[d.getDay()].slice(0, 2),
      F: () => MONATE[d.getMonth()], M: () => MONATE[d.getMonth()].slice(0, 3),
    };
    let out = '';
    for (let i = 0; i < f.length; i++) {
      const c = f[i];
      if (c === '\\') { out += f[++i] || ''; continue; }
      out += map[c] ? map[c]() : c;
    }
    return out;
  }

  /** Deutsch, angelehnt an Carbon::diffForHumans(). */
  diffForHumans() {
    const tage = Math.round(D.today().diffInDays(this.startOfDay()));
    if (tage === 0) return 'heute';
    if (tage === 1) return 'morgen';
    if (tage === -1) return 'gestern';
    const abs = Math.abs(tage);
    let text;
    if (abs < 7) text = `${abs} Tagen`;
    else if (abs < 30) { const w = Math.floor(abs / 7); text = w === 1 ? '1 Woche' : `${w} Wochen`; }
    else if (abs < 365) { const m = Math.floor(abs / 30); text = m === 1 ? '1 Monat' : `${m} Monaten`; }
    else { const j = Math.floor(abs / 365); text = j === 1 ? '1 Jahr' : `${j} Jahren`; }
    if (tage > 0) return `in ${text}`;
    return `vor ${text.replace(/Wochen$/, 'Wochen').replace(/^1 Woche$/, '1 Woche')}`;
  }
}

function daysInMonth(y, m0) { return new Date(y, m0 + 1, 0).getDate(); }

function isoWeekYear(date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const tag = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - tag);
  const jahrStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  return [d.getUTCFullYear(), Math.ceil(((d - jahrStart) / MS_TAG + 1) / 7)];
}

module.exports = { D, now: D.now, today: D.today, parse: D.parse };
