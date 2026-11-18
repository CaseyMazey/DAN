# DAN – Digitaler Ausbildungsnachweis (Desktop)

Lokale Einzelplatz-Version von DAN für **Windows, macOS und Linux**, die per Klick aufs Icon startet.
Es gibt **kein PHP, kein XAMPP, keinen MySQL-Server**, keine Admins, keine Ausbilder und keine Anmeldung:
DAN öffnet sich direkt (geschützt wird es durch dein Benutzerkonto am Rechner).

## Installieren

Die Installer gibt es unter [Releases](https://github.com/CaseyMazey/DAN/releases).

| System | Datei | Hinweis |
|---|---|---|
| Windows | `DAN-Setup-<version>.exe` | Doppelklick. Icon auf dem Desktop und im Startmenü. SmartScreen-Warnung: „Weitere Informationen“ → „Trotzdem ausführen“. |
| macOS (Apple Silicon) | `DAN-mac-arm64-<version>.dmg` | DMG öffnen, DAN in „Programme“ ziehen. Beim ersten Start **Rechtsklick → Öffnen** (App ist nicht von Apple beglaubigt). |
| macOS (Intel) | `DAN-mac-x64-<version>.dmg` | wie oben |
| Linux (Debian/Ubuntu) | `DAN-linux-amd64-<version>.deb` | `sudo apt install ./DAN-linux-amd64-<version>.deb` (empfohlen) |
| Linux (alle) | `DAN-linux-x86_64-<version>.AppImage` | `chmod +x` und starten |

Beim ersten Start gibst du einmal Name und Ausbildungszeitraum ein (oder stellst eine Sicherung wieder her),
danach öffnet sich DAN direkt.

Der lokale Server von DAN nimmt nur Anfragen aus dem DAN-Fenster an (zufälliger Schlüssel pro Start).
Andere Programme und Webseiten im Browser kommen nicht an deine Daten.

## Wo liegen meine Daten?

- Windows: `%APPDATA%\DAN\daten\`
- macOS: `~/Library/Application Support/DAN/daten/`
- Linux: `~/.config/DAN/daten/`

Darin:
- `dan.sqlite`: alle Wochenberichte, Aufgaben, Noten, Zeiträume
- `signaturen\`: deine Unterschrift als Bild

Sichern geht direkt in der App: **Mein Profil → Datensicherung**. Dort lädst du eine `.dan`-Datei herunter
oder stellst eine Sicherung wieder her (auch gleich auf der Einrichtungsseite nach einer Neuinstallation).
Beim Deinstallieren bleiben die Daten erhalten.

## Daten aus DAN2 übernehmen (einmalig)

XAMPP-MySQL muss laufen, DAN muss geschlossen sein:

```bash
node scripts/import-dan2.js --azubi 1
```

Das Skript übernimmt Berichte, Einträge, Aufgaben, Noten, Fächer, Zeiträume und die Signatur eines
Azubi-Kontos nach `%APPDATA%\DAN\daten`.

> Hinweis: Aus der Claude-Desktop-App heraus gestartete Programme schreiben AppData-Pfade in einen
> umgeleiteten Paketordner. Dann besser mit `--ziel` in einen normalen Ordner importieren, daraus eine
> `.dan`-Sicherung machen und diese in der App wiederherstellen.

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
npm run dist:win     # Installer bauen (auch dist:mac auf einem Mac, dist:linux unter Linux)
node scripts/start-check.js dist/win-unpacked/DAN.exe   # gebaute App starten und pruefen
node scripts/dev-server.js   # nur Server, im Browser unter http://127.0.0.1:8123 (Testdaten in .devdata)
```

Aufbau:
- `src/main.js`: Electron-Fenster; startet den lokalen Server auf 127.0.0.1
- `src/server/`: Express-Server, SQLite (eingebautes `node:sqlite`), Fachlogik, PDF (pdf-lib)
- `src/views/`: EJS-Vorlagen (Design aus der Laravel-Version übernommen)
- `src/public/`: CSS/JS/Schriften, `src/resources/`: IHK-Vorlage und Icon

## Neue Version veröffentlichen

Version in `package.json` erhöhen, committen, dann `git tag v<version>` und `git push --tags`.
GitHub Actions baut DAN auf Windows, macOS und Linux und legt das Release mit allen Installern an.
Bei jedem Push auf `main` laufen die Tests auf allen drei Systemen, dazu ein Probe-Build mit Startprüfung.
