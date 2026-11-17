'use strict';
// IHK-Wochenbericht als PDF: fuellt die offizielle Vorlage (resources/
// vorlage_wochenbericht.pdf) - Nachbau des frueheren FPDI/FPDF-Services mit
// pdf-lib. Koordinaten in mm ab Seitenoben links wie in der PHP-Version.

const fs = require('node:fs');
const path = require('node:path');
const { PDFDocument, StandardFonts, rgb } = require('pdf-lib');

const VORLAGE = path.join(__dirname, '..', 'resources', 'vorlage_wochenbericht.pdf');
const MM = 72 / 25.4;               // mm -> pt
const CELL_MARGIN = 1.0;            // FPDF-Standard-Innenabstand (mm)
const SEITE_H = 297;                // A4-Hoehe in mm

const SEKTIONEN = {
  betrieb: { stundenY: 90, inhaltY: 88, maxY: 126 },
  unterweisung: { stundenY: 141, inhaltY: 139, maxY: 176 },
  berufsschule: { stundenY: 191, inhaltY: 189, maxY: 227 },
};

let vorlageCache = null;
const vorlage = () => (vorlageCache ??= fs.readFileSync(VORLAGE));

/**
 * seiten: [{ bericht, eintraege, stunden, ausbildungsjahr }]
 * Liefert ein Uint8Array (eine Seite je Bericht).
 */
async function ihkPdf(seiten, azubi, dataDir) {
  const doc = await PDFDocument.create();
  doc.setTitle('Ausbildungsnachweis');
  doc.setCreator('DAN');
  const font = await doc.embedFont(StandardFonts.Helvetica);
  const src = await PDFDocument.load(vorlage());
  const signatur = await ladeSignatur(doc, azubi, dataDir);

  const [tpl] = await doc.embedPdf(src, [0]); // Vorlage nur einmal einbetten
  for (const s of seiten) {
    const page = doc.addPage([210 * MM, 297 * MM]);
    page.drawPage(tpl, { x: 0, y: 0, width: 210 * MM, height: 297 * MM });
    fuelleSeite(page, font, s, azubi, signatur);
  }
  return doc.save();
}

/** Sammel-PDF: gleiche IHK-Seiten, nur mehrere hintereinander. */
const sammelPdf = (azubi, seiten, dataDir) => ihkPdf(seiten, azubi, dataDir);

function fuelleSeite(page, font, { bericht, eintraege, stunden, ausbildungsjahr }, azubi, signatur) {
  const pen = stift(page, font);

  // Kopfdaten
  pen.write(85, 49, `${azubi.vorname} ${azubi.nachname}`, 10);
  pen.write(85, 58, `${ausbildungsjahr}. Jahr`, 10);
  pen.multiCell(151, 55, 55, 4, wrapText(azubi.abteilung || '', 22), 10);
  pen.write(85, 67, bericht.startdatum.format('d.m.Y'), 10);
  pen.write(116, 67, bericht.enddatum.format('d.m.Y'), 10);

  for (const [key, pos] of Object.entries(SEKTIONEN)) {
    if (stunden[key] != null) pen.write(180, pos.stundenY, Number(stunden[key]).toFixed(2), 10);
    const texte = Array.from(eintraege[key] || []).map(e => String(e.titel ?? e.beschreibung ?? ''));
    schreibeSektion(pen, texte, 27, pos.inhaltY, pos.maxY, 150);
  }

  if (bericht.azubi_signiert && signatur) {
    page.drawImage(signatur, { x: 27 * MM, y: (SEITE_H - 236 - 20) * MM, width: 60 * MM, height: 20 * MM });
  }
}

/** Schreibt alle Eintraege einer Sektion und verkleinert die Schrift, bis alles passt. */
function schreibeSektion(pen, texte, x, startY, maxY, breite) {
  if (!texte.length) return;
  const verfuegbar = maxY - startY;
  let size = 9, zeilenH = 5;
  for (size = 9; size >= 6; size -= 0.5) {
    zeilenH = 5 * size / 9;
    const gesamt = texte.reduce((n, t) => n + pen.lines(t, breite, size).length, 0);
    if (gesamt * zeilenH <= verfuegbar) break;
  }
  size = Math.max(size, 6);
  let y = startY;
  for (const t of texte) {
    if (y >= maxY) break;
    const zeilen = pen.lines(t, breite, size);
    for (const z of zeilen) {
      if (y >= maxY) break;
      pen.cellLine(x, y, zeilenH, z, size);
      y += zeilenH;
    }
  }
}

/** Kapselt FPDF-artige Textausgabe (mm, Ursprung oben links). */
function stift(page, font) {
  const sauber = saeubern(font);
  const breiteMm = (t, size) => font.widthOfTextAtSize(t, size) / MM;
  const ptToMm = (pt) => pt * 25.4 / 72;

  // FPDF: Write(0, ...) / Cell: Text bei x + cMargin, Grundlinie bei y + h/2 + 0.3 * Schriftgroesse.
  const cellLine = (x, y, h, text, size) => {
    const t = sauber(text);
    if (!t) return;
    const baseline = y + h / 2 + 0.3 * ptToMm(size);
    page.drawText(t, { x: (x + CELL_MARGIN) * MM, y: (SEITE_H - baseline) * MM, size, font, color: rgb(0, 0, 0) });
  };

  const lines = (text, breite, size) => {
    const max = breite - 2 * CELL_MARGIN;
    const out = [];
    for (const absatz of sauber(text).split('\n')) {
      let zeile = '';
      for (const wort of absatz.split(' ')) {
        const probe = zeile ? `${zeile} ${wort}` : wort;
        if (zeile && breiteMm(probe, size) > max) { out.push(zeile); zeile = wort; } else zeile = probe;
        // Einzelnes Wort breiter als die Zelle: hart umbrechen.
        while (breiteMm(zeile, size) > max && zeile.length > 1) {
          let i = zeile.length - 1;
          while (i > 1 && breiteMm(zeile.slice(0, i), size) > max) i--;
          out.push(zeile.slice(0, i)); zeile = zeile.slice(i);
        }
      }
      out.push(zeile);
    }
    return out.length ? out : [''];
  };

  return {
    cellLine,
    lines,
    write: (x, y, text, size) => cellLine(x, y, 0, text, size),
    multiCell: (x, y, w, h, text, size) => { let yy = y; for (const z of lines(text, w, size)) { cellLine(x, yy, h, z, size); yy += h; } },
  };
}

/** Entfernt Zeichen, die die Standardschrift (WinAnsi) nicht darstellen kann. */
function saeubern(font) {
  const erlaubt = new Set(font.getCharacterSet());
  return (text) => Array.from(String(text ?? '').replace(/\r\n?/g, '\n').replace(/\t/g, ' '))
    .map(c => (c === '\n' || erlaubt.has(c.codePointAt(0)) ? c : ersatz(c)))
    .join('');
}
const ERSATZ = { '–': '-', '—': '-', '„': '"', '“': '"', '”': '"', '‚': "'", '‘': "'", '’': "'", '…': '...', '•': '-', '→': '->', '✓': 'v' };
const ersatz = (c) => ERSATZ[c] ?? '';

function wrapText(text, maxChars) {
  const lines = [];
  let zeile = '';
  for (const w of String(text).split(' ')) {
    if (zeile === '') zeile = w;
    else if ((zeile + ' ' + w).length <= maxChars) zeile += ' ' + w;
    else { lines.push(zeile); zeile = w; }
  }
  if (zeile !== '') lines.push(zeile);
  return lines.join('\n');
}

async function ladeSignatur(doc, azubi, dataDir) {
  if (!azubi.signatur_pfad) return null;
  const datei = path.join(dataDir, azubi.signatur_pfad);
  if (!datei.startsWith(path.join(dataDir, 'signaturen')) || !fs.existsSync(datei)) return null;
  const bytes = fs.readFileSync(datei);
  try {
    if (bytes[0] === 0x89 && bytes[1] === 0x50) return await doc.embedPng(bytes);
    if (bytes[0] === 0xff && bytes[1] === 0xd8) return await doc.embedJpg(bytes);
  } catch { /* defektes Bild: ohne Signatur weiter */ }
  return null;
}

module.exports = { ihkPdf, sammelPdf };
