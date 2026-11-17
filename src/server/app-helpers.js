'use strict';
/** 404-Fehler werfen (wie findOrFail in Laravel). */
function notFound() { const e = new Error('Nicht gefunden'); e.status = 404; return e; }
function orFail(row) { if (!row) throw notFound(); return row; }
module.exports = { notFound, orFail };
