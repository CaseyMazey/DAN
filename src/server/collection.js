'use strict';
// Schlanke Nachbildung der Laravel-Collection-Methoden, die in Logik und
// Vorlagen benutzt werden (count, sum, groupBy, pluck, ...).

const val = (item, key) => (typeof key === 'function' ? key(item) : key == null ? item : item?.[key]);

class C extends Array {
  static from(arr) { const c = new C(); for (const x of arr || []) c.push(x); return c; }
  static get [Symbol.species]() { return C; }

  count() { return this.length; }
  isEmpty() { return this.length === 0; }
  isNotEmpty() { return this.length > 0; }
  first(fn) { return (fn ? this.find(fn) : this[0]) ?? null; }
  last() { return this.length ? this[this.length - 1] : null; }
  values() { return C.from(this); }
  all() { return Array.from(this); }
  sum(key) { return this.reduce((s, x) => s + (Number(val(x, key)) || 0), 0); }
  avg(key) { return this.length ? this.sum(key) / this.length : null; }
  pluck(key) { return this.map(x => x?.[key]); }
  where(key, v) { return this.filter(x => x?.[key] === v); }
  whereIn(key, vs) { return this.filter(x => vs.includes(x?.[key])); }
  contains(fn) { return typeof fn === 'function' ? this.some(fn) : this.includes(fn); }
  unique(key) { const seen = new Set(); return this.filter(x => { const k = val(x, key); if (seen.has(k)) return false; seen.add(k); return true; }); }
  sortBy(key, desc = false) { return C.from([...this].sort((a, b) => cmp(val(a, key), val(b, key)) * (desc ? -1 : 1))); }
  sortByDesc(key) { return this.sortBy(key, true); }
  sortDesc() { return C.from([...this].sort((a, b) => cmp(b, a))); }
  take(n) { return C.from(this.slice(0, n)); }
  implode(sep) { return this.join(sep); }
  concat(...o) { return C.from(Array.prototype.concat.apply(Array.from(this), o.map(x => Array.from(x)))); }
  groupBy(key) {
    const g = new Group();
    for (const x of this) { const k = val(x, key); if (!g.has(k)) g.set(k, new C()); g.get(k).push(x); }
    return g;
  }
  keyBy(key) { const g = new Group(); for (const x of this) g.set(val(x, key), x); return g; }
}

/** Ergebnis von groupBy(): Map mit get(key, default) wie Laravel. */
class Group extends Map {
  get(k, def = null) { return super.has(k) ? super.get(k) : def; }
  count() { return this.size; }
  isEmpty() { return this.size === 0; }
  isNotEmpty() { return this.size > 0; }
  keys() { return C.from(super.keys()); }
  values() { return C.from(super.values()); }
  map(fn) { const g = new Group(); for (const [k, v] of this) g.set(k, fn(v, k)); return g; }
  sum(fn) { let s = 0; for (const [k, v] of this) s += Number(val(v, fn)) || 0; return s; }
  flatMap(fn) { const out = new C(); for (const [k, v] of this) for (const x of fn(v, k)) out.push(x); return out; }
}

function cmp(a, b) {
  if (a == null && b == null) return 0;
  if (a == null) return -1;
  if (b == null) return 1;
  if (typeof a === 'string' && typeof b === 'string') return a.localeCompare(b, 'de');
  return a < b ? -1 : a > b ? 1 : 0;
}

const collect = (arr) => C.from(arr || []);

module.exports = { C, Group, collect };
