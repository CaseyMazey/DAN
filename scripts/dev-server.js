'use strict';
// Startet den DAN-Server ohne Electron (zum Entwickeln/Testen im Browser).
// Daten landen in ./.devdata
const path = require('node:path');
process.env.DAN_DEV = '1';
const { createApp } = require('../src/server/app');
const dataDir = process.env.DAN_DATA || path.join(__dirname, '..', '.devdata');
const app = createApp({ dataDir });
const port = Number(process.env.PORT || 8123);
app.listen(port, '127.0.0.1', () => console.log(`DAN dev: http://127.0.0.1:${port}  (Daten: ${dataDir})`));
