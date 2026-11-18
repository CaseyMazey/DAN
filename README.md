# DAN – Digitaler Ausbildungsnachweis (Desktop)

Lokale Einzelplatz-Version von DAN: eine Windows-App, die per Klick aufs Icon startet.
Es gibt **kein PHP, kein XAMPP, keinen MySQL-Server**, keine Admins, keine Ausbilder und keine Anmeldung:
DAN öffnet sich direkt (geschützt wird es durch dein Windows-Konto).

## Installieren

`dist/DAN-Setup-<version>.exe` doppelklicken. Das Setup installiert DAN für deinen
Windows-Benutzer und legt ein Icon auf dem Desktop und im Startmenü an.
Beim ersten Start gibst du einmal Name und Ausbildungszeitraum ein, danach öffnet sich DAN direkt.

Der lokale Server von DAN nimmt nur Anfragen aus dem DAN-Fenster an (zufälliger Schlüssel pro Start).
Andere Programme und Webseiten im Browser kommen nicht an deine Daten.

## Wo liegen meine Daten?

`%APPDATA%\DAN\daten\`
- `dan.sqlite`: alle Wochenberichte, Aufgaben, Noten, Zeiträume
- `signaturen\`: deine Unterschrift als Bild

Für eine Sicherung kopierst du diesen Ordner, während DAN geschlossen ist.
Beim Deinstallieren bleiben die Daten erhalten.

## Daten aus DAN2 übernehmen (einmalig)

XAMPP-MySQL muss laufen, DAN muss geschlossen sein:

```bash
node scripts/import-dan2.js --azubi 1
```

Das Skript übernimmt Berichte, Einträge, Aufgaben, Noten, Fächer, Zeiträume und die Signatur eines
Azubi-Kontos nach `%APPDATA%\DAN\daten`.

## Funktionen

- Wochenberichte mit Live-Vorschau, Abgabe, Nachträgen (mit Pflichtkommentar) und Signatur
- IHK-PDF (offizielle Vorlage) einzeln und als Sammel-PDF
- Aufgaben-Kanban mit Stunden-Soll
- Noten & Termine pro Ausbildungsjahr
- Zeiträume (Urlaub, Krankheit, Praktikum …), die die Woche im Bericht überschreiben
- Die Vorwoche wird beim Öffnen automatisch abgegeben, falls noch offen
  (ersetzt den früheren Freitags-Cron des Servers)

## Entwicklung

```bash
npm install
npm start            # App im Entwicklungsmodus starten
npm test             # Tests
npm run dist         # Installer bauen -> dist/DAN-Setup-<version>.exe
node scripts/dev-server.js   # nur Server, im Browser unter http://127.0.0.1:8123 (Testdaten in .devdata)
```

Aufbau:
- `src/main.js`: Electron-Fenster; startet den lokalen Server auf 127.0.0.1
- `src/server/`: Express-Server, SQLite (eingebautes `node:sqlite`), Fachlogik, PDF (pdf-lib)
- `src/views/`: EJS-Vorlagen (Design aus der Laravel-Version übernommen)
- `src/public/`: CSS/JS/Schriften, `src/resources/`: IHK-Vorlage und Icon
