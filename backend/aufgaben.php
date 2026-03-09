<?php
/* -------------------------------------------------------
   SESSION & GRUNDSETUP
------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'aufgaben.php';

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stunden_utils.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

/* -------------------------------------------------------
   STUNDENKONFIG UND NUTZUNG LADEN
-------------------------------------------------------- */
$stunden_config = getStundenConfigUser($conn, $user_id);
$stunden_usage  = getWeeklyHoursUsage($conn, $user_id);

$betrieb_used = $stunden_usage['betrieb']      ?? 0;
$schule_used  = $stunden_usage['berufsschule'] ?? 0;
$gesamt_used  = $stunden_usage['gesamt']       ?? 0;

$formatHoursInput = fn($value) => 
    rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.') ?: '0';

$percent = fn($used, $max) =>
    ($max > 0) ? min(100, ($used / $max) * 100) : 0;

/* -------------------------------------------------------
   AUTOMATISCH: ALTE "FERTIG"-AUFGABEN ARCHIVIEREN
------------------------------------------------------- */
function archiveOldFinishedTasks(mysqli $conn, int $userId): void {
    $mondayStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
    if (!$mondayStart) return;

    $stmt = $conn->prepare("
        UPDATE aufgaben
        SET archiviert = 1,
            archiviert_am = CASE 
                WHEN archiviert_am IS NULL THEN ? 
                ELSE archiviert_am 
            END
        WHERE azubi_id = ?
          AND status = 'fertig'
          AND archiviert = 0
          AND geaendert_am < ?
    ");
    $stmt->bind_param("sis", $mondayStart, $userId, $mondayStart);
    $stmt->execute();
    $stmt->close();
}

archiveOldFinishedTasks($conn, $user_id);

/* -------------------------------------------------------
   HELFERFUNKTIONEN (STATUS, KATEGORIE, STUNDEN)
------------------------------------------------------- */
function statusLabel(string $status): string {
    return ucfirst(str_replace('_', ' ', $status));
}

function hoursInfo($stunden, ?string $type): string {
    $num = (float)$stunden;
    if ($num <= 0) return '';
    $label = $type === 'berufsschule' ? 'Berufsschule' : 'Betrieb';
    return number_format($num, 2, ',', '.') . " Std : {$label}";
}

function categoryLabel(?string $value): string {
    $map = [
        'betrieb'       => 'Betrieb',
        'berufsschule'  => 'Berufsschule',
        'unterweisung'  => 'Unterweisung'
    ];
    $key = strtolower(trim((string)$value));
    return $map[$key] ?? ucfirst($value);
}

/* -------------------------------------------------------
   ZEITSPANNE FÜR WOCHENLABEL
------------------------------------------------------- */
$stunden_week_label = '';
if (!empty($stunden_usage['week_start']) && !empty($stunden_usage['week_end'])) {
    $stunden_week_label =
        date('d.m.', strtotime($stunden_usage['week_start'])) .
        ' - ' .
        date('d.m.Y', strtotime($stunden_usage['week_end']));
}

$betrieb_percent = $percent($betrieb_used, $stunden_config['max_betrieb'] ?? 0);
$schule_percent  = $percent($schule_used,  $stunden_config['max_berufsschule'] ?? 0);

/* -------------------------------------------------------
   AKTIVE AUFGABEN LADEN (NICHT ARCHIVIERT)
------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT * 
    FROM aufgaben 
    WHERE azubi_id = ? 
      AND archiviert = 0 
    ORDER BY erstellt_am DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$aufgaben_result = $stmt->get_result();

/* Aufgaben in Kategorien sortieren */
$aufgaben = [
    'offen'          => [],
    'in_bearbeitung' => [],
    'fertig'         => []
];

while ($row = $aufgaben_result->fetch_assoc()) {
    $status = $row['status'] ?? 'offen';
    $aufgaben[$status][] = $row;
}
$stmt->close();

/* -------------------------------------------------------
   ARCHIVIERTE AUFGABEN LADEN (MAX 20)
------------------------------------------------------- */
$archivierte_aufgaben = [];

$arch_stmt = $conn->prepare("
    SELECT * 
    FROM aufgaben 
    WHERE azubi_id = ? 
      AND archiviert = 1 
    ORDER BY COALESCE(archiviert_am, geaendert_am) DESC 
    LIMIT 20
");
$arch_stmt->bind_param("i", $user_id);
$arch_stmt->execute();

$arch_res = $arch_stmt->get_result();

while ($row = $arch_res->fetch_assoc()) {
    $archivierte_aufgaben[] = $row;
}

$arch_stmt->close();

/* -------------------------------------------------------
   AUSGABEPUFFER STARTEN
------------------------------------------------------- */
ob_start();
?>
<link rel="stylesheet" href="dan-base.css">
<link rel="stylesheet" href="dan-layout.css">
<link rel="stylesheet" href="aufgaben.css"> <!-- Deine Seite-spezifischen Styles -->

    <div class="container my-4">

        <!-- ----------------------------------------------
            OBERER BEREICH: Überschrift & Buttons
        ------------------------------------------------ -->
        <div class="d-flex justify-content-between mb-3 align-items-center flex-wrap gap-3">
            <h1 class="mb-0">Meine Aufgaben</h1>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#stundenConfigModal">
                    Generelle Stunden
                </button>

                <button class="btn btn-success" 
                        data-bs-toggle="modal" 
                        data-bs-target="#neuAufgabeModal">
                    + Neue Aufgabe
                </button>
            </div>
        </div>


        <!-- ----------------------------------------------
            STUNDEN-ÜBERSICHT (Top-Karte)
        ------------------------------------------------ -->
        <div class="hours-summary mb-4 shadow-sm rounded-3 p-3" 
            id="stundenSummary"
            style="background: #ffffffd9; backdrop-filter: blur(6px);">

            <!-- Woche -->
            <div class="mb-3">
                <div class="stat-label fw-semibold text-secondary">Aktuelle Woche</div>

                <div class="stat-value fs-5" id="stundenWeekLabel">
                    <?= $stunden_week_label ?: 'Aktuelle Woche' ?>
                </div>
            </div>

            <hr class="my-3">

            <!-- Drei Spalten: Betrieb / Schule / Gesamt -->
            <div class="row text-center g-3">

                <div class="col-md-4">
                    <div class="stat-label fw-semibold text-secondary">Betrieb</div>
                    <div class="stat-value fs-5 mt-1" id="stat-betrieb">
                        <?= number_format($stunden_config['max_betrieb'] ?? 0, 2, ',', '.') ?> Std
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-label fw-semibold text-secondary">Berufsschule</div>
                    <div class="stat-value fs-5 mt-1" id="stat-schule">
                        <?= number_format($stunden_config['max_berufsschule'] ?? 0, 2, ',', '.') ?> Std
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-label fw-semibold text-secondary">Gesamt</div>
                    <div class="stat-value fs-5 mt-1" id="stat-gesamt">
                        <span id="stundenLimit">
                            <?= number_format($stunden_config['max_woche'] ?? 0, 2, ',', '.') ?>
                        </span>
                        Std
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- ---------------------------------------------------------
        KANBAN-BOARD
    ----------------------------------------------------------- -->
    <div class="kanban-board">

        <?php 
        foreach ([
            'offen'          => 'Geplant',
            'in_bearbeitung' => 'In Bearbeitung',
            'fertig'         => 'Fertig'
        ] as $status_key => $status_label): 
        ?>

            <div class="kanban-column" data-status="<?= $status_key ?>">
                <h4><?= htmlspecialchars($status_label) ?></h4>

                <?php foreach ($aufgaben[$status_key] as $aufg): 
                    $stundenInfo = hoursInfo($aufg['stunden'], $aufg['stunden_typ'] ?? null);
                ?>

                    <div class="kanban-card"
                        data-id="<?= (int)$aufg['id'] ?>"
                        data-titel="<?= htmlspecialchars($aufg['titel']) ?>"
                        data-beschreibung="<?= htmlspecialchars($aufg['beschreibung'] ?? '') ?>"
                        data-kategorie="<?= htmlspecialchars($aufg['kategorie']) ?>"
                        data-status="<?= htmlspecialchars($aufg['status']) ?>"
                        data-stunden="<?= htmlspecialchars($aufg['stunden']) ?>"
                        data-stunden_typ="<?= htmlspecialchars($aufg['stunden_typ'] ?? '') ?>"
                        draggable="true">

                        <div class="task-left">
                            <strong class="task-title">
                                <?= htmlspecialchars($aufg['titel']) ?>
                            </strong>

                            <p><?= nl2br(htmlspecialchars($aufg['beschreibung'] ?? '')) ?></p>

                            <small class="text-muted">
                                <?= htmlspecialchars(categoryLabel($aufg['kategorie'])) ?>
                            </small>

                            <?php if ($stundenInfo): ?>
                                <div class="task-hours small fw-semibold">
                                    <?= $stundenInfo ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="task-right task-actions">

                            <!-- ****************************
                                EDIT-BUTTON (Stift)
                            ***************************** -->
                            <button type="button" 
                                    class="editButton edit-task"
                                    aria-label="Aufgabe bearbeiten">
                                <svg viewBox="0 0 24 24" class="pencil">
                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="#B5BAC1"></path>
                                    <path d="M20.71 7.04a1.004 1.004 0 000-1.42l-2.34-2.34a1.004 1.004 0 00-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z" fill="#B5BAC1"></path>
                                </svg>
                                <span class="tooltip">Bearbeiten</span>
                            </button>

                            <!-- ****************************
                                ARCHIVIEREN-BUTTON
                            ***************************** -->
                            <button type="button"
                                    class="archive-task archive-task-btn"
                                    aria-label="Aufgabe archivieren">
                                <svg viewBox="0 0 24 24" class="archive-icon" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                <span class="tooltip">Archivieren</span>
                            </button>

                            <!-- ****************************
                                DELETE-BUTTON (Mülleimer)
                            ***************************** -->
                            <button type="button" 
                                    class="deleteButton delete-task"
                                    aria-label="Aufgabe löschen">
                                <svg xmlns="http://www.w3.org/2000/svg" 
                                    fill="none" 
                                    viewBox="0 0 50 59" 
                                    class="bin">
                                    <path fill="#B5BAC1" d="M0 7.5C0 5.01472 2.01472 3 4.5 3H45.5C47.9853 3 50 5.01472 50 7.5V7.5C50 8.32843 49.3284 9 48.5 9H1.5C0.671571 9 0 8.32843 0 7.5V7.5Z"></path>
                                    <path fill="#B5BAC1" d="M17 3C17 1.34315 18.3431 0 20 0H29.3125C30.9694 0 32.3125 1.34315 32.3125 3V3H17V3Z"></path>
                                    <path fill="#B5BAC1" d="M2.18565 18.0974C2.08466 15.821 3.903 13.9202 6.18172 13.9202H43.8189C46.0976 13.9202 47.916 15.821 47.815 18.0975L46.1699 55.1775C46.0751 57.3155 44.314 59.0002 42.1739 59.0002H7.8268C5.68661 59.0002 3.92559 57.3155 3.83073 55.1775L2.18565 18.0974ZM18.0003 49.5402C16.6196 49.5402 15.5003 48.4209 15.5003 47.0402V24.9602C15.5003 23.5795 16.6196 22.4602 18.0003 22.4602C19.381 22.4602 20.5003 23.5795 20.5003 24.9602V47.0402C20.5003 48.4209 19.381 49.5402 18.0003 49.5402ZM29.5003 47.0402C29.5003 48.4209 30.6196 49.5402 32.0003 49.5402C33.381 49.5402 34.5003 48.4209 34.5003 47.0402V24.9602C34.5003 23.5795 33.381 22.4602 32.0003 22.4602C30.6196 22.4602 29.5003 23.5795 29.5003 24.9602V47.0402Z" clip-rule="evenodd" fill-rule="evenodd"></path>
                                    <path fill="#B5BAC1" d="M2 13H48L47.6742 21.28H2.32031L2 13Z"></path>
                                </svg>
                                <span class="tooltip">Löschen</span>
                            </button>

                        </div>

            <!-- Badge (ausgeblendet in deinem CSS, aber strukturiert vorhanden) -->
                        <span class="badge-status badge-<?= htmlspecialchars($aufg['status']) ?>">
                            <?= statusLabel($aufg['status']) ?>
                        </span>

                    </div> <!-- /kanban-card -->

                <?php endforeach; ?>

            </div> <!-- /kanban-column -->

        <?php endforeach; ?>

    </div> <!-- /kanban-board -->


    <!-- ===================================================================
        ARCHIVIERTE AUFGABEN
        -------------------------------------------------------------------
        Dieser Bereich zeigt alle archivierten Aufgaben an.
        - Max. 20 Eintäge (weil ältere automatisch ausblenden)
        - Jede Aufgabe kann wiederhergestellt werden
        - Schön kompakt, aber klar strukturiert
    ===================================================================== -->
    <div class="archive-card mt-4" id="archiveWrapper">

        <!-- Kopfzeile -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0">Archivierte Aufgaben</h4>
            <small class="text-muted">Maximal 20 Einträge werden angezeigt.</small>
        </div>

        <div id="archiveList" class="mt-3">

            <?php foreach ($archivierte_aufgaben as $arch): 
                // Datum formatieren für Anzeige
                $archDate      = $arch['archiviert_am'] ?: $arch['geaendert_am'];
                $archFormatted = $archDate ? date('d.m.Y H:i', strtotime($archDate)) : '-';
                $archKategory  = categoryLabel($arch['kategorie']);
            ?>
            
                <!-- Jede archivierte Aufgabe -->
                <div class="archive-entry" data-archive-id="<?= (int)$arch['id'] ?>">

                    <!-- Titel + Aktion rechts -->
                    <div class="d-flex justify-content-between align-items-center">

                        <strong><?= htmlspecialchars($arch['titel']) ?></strong>

                        <div class="archive-actions">
                            
                            <!-- Badge zeigt Kategorie (z.B. Betrieb / Unterweisung / Schule) -->
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($archKategory) ?>
                            </span>

                            <!-- WIEDERHERSTELLEN Button
                                (holt Aufgabe zurück ins Kanban-Board)
                            -->
                            <button type="button"
                                    class="archive-restore-btn"
                                    aria-label="Aufgabe wiederherstellen">
                                
                                <!-- Icon -->
                                <svg class="restore-icon"
                                    xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 24 24" fill="none" 
                                    stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 14 4 9 9 4"></polyline>
                                    <path d="M20 20v-5a7 7 0 0 0-7-7H4"></path>
                                </svg>

                                <span class="tooltip">Wiederherstellen</span>
                            </button>

                        </div>
                    </div>

                    <!-- Meta-Infos -->
                    <div class="meta">
                        Beendet am <?= htmlspecialchars($archFormatted) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- Meldung: Archiv ist leer -->
        <p class="text-muted mt-3 <?= !empty($archivierte_aufgaben) ? 'd-none' : '' ?>"
        id="archiveEmpty">
        Noch keine archivierten Aufgaben.
        </p>

    </div>



    <!-- ===================================================================
        MODAL: NEUE AUFGABE ERSTELLEN
        -------------------------------------------------------------------
        Dieses Modal wird geöffnet, wenn der Benutzer eine neue Aufgabe
        hinzufügen möchte. 
        Es unterstützt:
        - Titel, Beschreibung
        - Kategorie (regelt automatisch die Stundenfelder)
        - Optionale Stunden (wenn KEINE Schulung)
        - Pflichtstunden + Abzugsart (wenn Schulung)
        - Status beim Erstellen
    ===================================================================== -->
    <div class="modal fade" id="neuAufgabeModal" tabindex="-1"
        aria-labelledby="neuAufgabeModalLabel" aria-hidden="true">

    <div class="modal-dialog">
        <form id="aufgabeForm">
        <div class="modal-content">

            <div class="modal-header">
            <h5 class="modal-title">Neue Aufgabe erstellen</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Schließen"></button>
            </div>

            <div class="modal-body">

            <!-- Titel -->
            <div class="mb-3">
                <label class="form-label">Titel</label>
                <input type="text" name="titel" class="form-control" required maxlength="30">
            </div>

            <!-- Beschreibung -->
            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="beschreibung" class="form-control" maxlength="150"></textarea>
            </div>

            <!-- Kategorie -->
            <div class="mb-3">
                <label class="form-label">Kategorie</label>
                <select name="kategorie" class="form-select" required>
                <option value="betrieb">Betrieb</option>
                <option value="unterweisung">Unterweisung / Schulung</option>
                <option value="berufsschule">Berufsschule</option>
                </select>
            </div>

            <!-- Optionale Stunden (wenn keine Schulung) -->
            <div class="mb-3" id="optionalHoursGroup">
                <label class="form-label mb-0">Stunden (optional)</label>
                <input type="number" step="0.25" min="0"
                    name="stunden" class="form-control"
                    id="optionalStunden"
                    placeholder="z. B. 4">
                <small class="form-text text-muted">
                    Optionaler Zeitaufwand wenn keine Schulung.
                </small>
            </div>

            <!-- Pflicht-Stunden + Abzug bei Schulung -->
            <div class="mb-3 training-fields d-none" id="trainingFields">

                <div class="mb-2">
                <label class="form-label mb-0">Stunden (Pflicht bei Schulung)</label>
                <input type="number" step="0.25" min="0"
                        name="stunden" class="form-control"
                        id="trainingStunden"
                        placeholder="z. B. 4" disabled>
                </div>

                <div>
                <label class="form-label mb-0">Stunden abziehen bei</label>
                <select name="stunden_typ" class="form-select"
                        id="trainingStundenTyp" disabled>
                    <option value="betrieb">Betrieb</option>
                    <option value="berufsschule">Berufsschule</option>
                </select>
                <small class="form-text text-muted">
                    Schulungsstunden werden in das entsprechende Stundenkonto eingetragen.
                </small>
                </div>

            </div>

            <!-- Status -->
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                <option value="offen">Geplant</option>
                <option value="in_bearbeitung">In Bearbeitung</option>
                <option value="fertig">Fertig</option>
                </select>
            </div>

            </div>

            <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Speichern</button>
            </div>

        </div>
        </form>
    </div>
    </div>



    <!-- ===================================================================
        MODAL: AUFGABE BEARBEITEN
        -------------------------------------------------------------------
        Funktioniert genauso wie das Neue-Aufgabe-Modal.
        Der Unterschied:
        - Das Modal wird per JS mit den aktuellen Werten der Aufgabe befüllt.
        - Kategorie-Umschaltung funktioniert genauso wie oben.
    ===================================================================== -->
    <div class="modal fade" id="editAufgabeModal" tabindex="-1"
        aria-labelledby="editAufgabeModalLabel" aria-hidden="true">

    <div class="modal-dialog">
        <form id="editAufgabeForm">
        <input type="hidden" name="id" id="editAufgabeId">

        <div class="modal-content">

            <div class="modal-header">
            <h5 class="modal-title">Aufgabe bearbeiten</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Schließen"></button>
            </div>

            <div class="modal-body">

            <!-- Titel -->
            <div class="mb-3">
                <label class="form-label">Titel</label>
                <input type="text" name="titel" class="form-control"
                    id="editTitel" required maxlength="30">
            </div>

            <!-- Beschreibung -->
            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="beschreibung" class="form-control"
                        id="editBeschreibung" maxlength="150"></textarea>
            </div>

            <!-- Kategorie -->
            <div class="mb-3">
                <label class="form-label">Kategorie</label>
                <select name="kategorie" class="form-select"
                        id="editKategorie" required>
                <option value="betrieb">Betrieb</option>
                <option value="unterweisung">Unterweisung / Schulung</option>
                <option value="berufsschule">Berufsschule</option>
                </select>
            </div>

            <!-- Optional Stunden -->
            <div class="mb-3" id="editOptionalHoursGroup">
                <label class="form-label mb-0">Stunden (optional)</label>
                <input type="number" step="0.25" min="0"
                    name="stunden" class="form-control"
                    id="editOptionalStunden"
                    placeholder="z. B. 4">
            </div>

            <!-- Pflicht-Stunden bei Schulung -->
            <div class="mb-3 d-none" id="editTrainingFields">

                <div class="mb-2">
                <label class="form-label mb-0">Stunden (Pflicht bei Schulung)</label>
                <input type="number" step="0.25" min="0"
                        name="stunden" class="form-control"
                        id="editStunden" placeholder="z. B. 4"
                        disabled>
                </div>

                <div>
                <label class="form-label mb-0">Stunden abziehen bei</label>
                <select name="stunden_typ" class="form-select"
                        id="editStundenTyp" disabled>
                    <option value="betrieb">Betrieb</option>
                    <option value="berufsschule">Berufsschule</option>
                </select>
                </div>

            </div>

            <!-- Status -->
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" id="editStatus">
                <option value="offen">Geplant</option>
                <option value="in_bearbeitung">In Bearbeitung</option>
                <option value="fertig">Fertig</option>
                </select>
            </div>

            </div>

            <div class="modal-footer">
            <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-primary">Aktualisieren</button>
            </div>

        </div>
        </form>
    </div>
    </div>


</div>

<!-- ========================================================================
     MODAL: "Generelle Stunden"
     ------------------------------------------------------------------------
     Dieses Modal dient zur Konfiguration deiner Wochenstunden.
     Der Benutzer kann hier einstellen:
       - maximale Gesamtstunden pro Woche
       - maximale Stunden für Betrieb
       - maximale Stunden für Berufsschule

     Wichtig:
     - Die Werte werden beim öffnen automatisch befüllt.
     - Beim Speichern sendet JS (unten) die Daten per AJAX an stunden_config_api.php
     - Nach erfolgreich gespeichertem Update wird direkt die Stundenübersicht oben aktualisiert.
============================================================================= -->
<div class="modal fade" id="stundenConfigModal" tabindex="-1" 
     aria-labelledby="stundenConfigModalLabel" aria-hidden="true">
  <div class="modal-dialog">

    <!-- Das Formular wird per JS ausgewertet -->
    <form id="stundenConfigForm" class="modal-content">

      <!-- Kopfzeile des Modals -->
      <div class="modal-header">
        <h5 class="modal-title" id="stundenConfigModalLabel">Generelle Stunden</h5>

        <!-- Bootstrap-Standard-Schließen-Button -->
        <button type="button" class="btn-close" 
                data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>

      <!-- Inhalt (Formularfelder) -->
      <div class="modal-body">

        <!-- Fehler/Statusanzeige (wird per JS sichtbar gemacht) -->
        <div class="alert alert-danger d-none" id="stundenConfigFeedback"></div>

        <!-- Max. Stunden pro Woche -->
        <div class="mb-3">
            <label class="form-label">Maximale Stunden pro Woche</label>

            <!-- wird bei jedem Aufruf automatisch befüllt -->
            <input type="number" step="0.5" min="0" name="max_woche" 
                   class="form-control"
                   value="<?= $formatHoursInput($stunden_config['max_woche'] ?? 0) ?>"
                   required>

            <small class="text-muted">
                Die Summe aus Betrieb + Berufsschule sollte idealerweise dieses Wochenlimit nicht überschreiten.
            </small>
        </div>

        <!-- Max. Stunden Betrieb -->
        <div class="mb-3">
            <label class="form-label">Betrieb</label>
            <input type="number" step="0.5" min="0" name="max_betrieb" 
                   class="form-control"
                   value="<?= $formatHoursInput($stunden_config['max_betrieb'] ?? 0) ?>"
                   required>
        </div>

        <!-- Max. Stunden Berufsschule -->
        <div class="mb-3">
            <label class="form-label">Berufsschule</label>
            <input type="number" step="0.5" min="0" name="max_berufsschule"
                   class="form-control"
                   value="<?= $formatHoursInput($stunden_config['max_berufsschule'] ?? 0) ?>"
                   required>
        </div>

      </div>

      <!-- Fußbereich des Modals -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" 
                data-bs-dismiss="modal">Abbrechen</button>
        <button type="submit" class="btn btn-primary">
            Speichern
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ========================================================================
     JAVASCRIPT-IMPORTS (jQuery + Bootstrap Bundle)
     ------------------------------------------------------------------------
     Für:
       - Modals (öffnen/schließen)
       - AJAX Kommunikation
       - Event Handling
============================================================================= -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
if (typeof window.jQuery === 'undefined') {
    document.write('<script src="js/jquery-3.7.1.min.js"><\\/script>');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ==========================================================================
   JAVASCRIPT START  GRUNDEINSTELLUNGEN & HILFSVARIABLEN
   --------------------------------------------------------------------------
   Hier initialisieren wir alle benötigten DOM-Elemente und Tools.
   Dazu gehören:
     - hourFormatter ? formatiert Stunden in deutsches Format
     - Selektoren für Formulare, Modals, Eingaben
     - Variablen zum Speichern von Referenzen (z. B. für Bearbeiten)
     - Hilfsfunktionen fürs Schließen der Modals
   ========================================================================== */

(function() {

if (typeof window.jQuery === 'undefined') {
    console.error('DAN Aufgaben: jQuery konnte nicht geladen werden. Skript gestoppt.');
    return;
}

const $ = window.jQuery;
const Bootstrap = window.bootstrap || null;

let draggedCard = null;  // Wird beim Drag&Drop gesetzt

/* Formatierer für Stundenwerte (z.B. 1.5 ? 1,50) */
const hourFormatter = new Intl.NumberFormat('de-DE', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
});

/* ----------- DOM-Elemente für Neue-Aufgabe-Modal ----------- */
const categorySelect         = $('select[name="kategorie"]');
const optionalHoursGroup     = $('#optionalHoursGroup');
const optionalStundenInput   = $('#optionalStunden');
const trainingFields         = $('#trainingFields');
const trainingStundenInput   = $('#trainingStunden');
const stundenTypSelect       = $('#trainingStundenTyp');

/* ----------- Archiv-DOM ----------- */
const archiveList            = $('#archiveList');
const archiveEmpty           = $('#archiveEmpty');

/* ----------- Modals initialisieren ----------- */
const editModalEl            = document.getElementById('editAufgabeModal');
const editModal              = (editModalEl && Bootstrap && typeof Bootstrap.Modal === 'function')
    ? new Bootstrap.Modal(editModalEl)
    : null;

const newTaskModalEl         = document.getElementById('neuAufgabeModal');
const newTaskModal           = (newTaskModalEl && Bootstrap && Bootstrap.Modal?.getOrCreateInstance)
    ? Bootstrap.Modal.getOrCreateInstance(newTaskModalEl)
    : null;

const stundenConfigModalEl   = document.getElementById('stundenConfigModal');
const stundenConfigModal     = (stundenConfigModalEl && Bootstrap && Bootstrap.Modal?.getOrCreateInstance)
    ? Bootstrap.Modal.getOrCreateInstance(stundenConfigModalEl)
    : null;

/* ----------- Formular für Bearbeitung einer Aufgabe ----------- */
const editForm               = $('#editAufgabeForm');
const editCategorySelect     = $('#editKategorie');
const editOptionalHoursGroup = $('#editOptionalHoursGroup');
const editOptionalStundenInput = $('#editOptionalStunden');
const editTrainingFields     = $('#editTrainingFields');
const editStundenInput       = $('#editStunden');
const editStundenTypSelect   = $('#editStundenTyp');
const editStatusSelect       = $('#editStatus');

let editCardRef = null;  // Referenz auf die Karte, die gerade bearbeitet wird


/* ==========================================================================
   HELFERFUNKTIONEN
   --------------------------------------------------------------------------
   Diese Funktionen werden an vielen Stellen im Code benötigt.
   Sie sind zentral gesammelt, damit du sie überall wiederverwenden kannst.
   ========================================================================== */

/* --------------------------------------------------------------
   escapeHtml()
   --------------------------------------------------------------
   Entfernt HTML-Sonderzeichen aus Strings, bevor sie in das DOM
   eingesetzt werden.  
   Verhindert u.a. XSS-Probleme und kaputtes Markup.
---------------------------------------------------------------- */
function handleAjaxFailWithSuccessFallback(xhr, successCallback, defaultMessage) {
    let data = null;

    try {
        if (xhr && xhr.responseText) {
            data = JSON.parse(xhr.responseText);
        }
    } catch (e) {
        // JSON konnte nicht geparsed werden → ignorieren
    }

    // Server meldet Erfolg ? kein Fehler anzeigen
    if (data && data.success) {
        if (typeof successCallback === 'function') {
            successCallback(data);
        }
        return;
    }

    // Fehler mit serverseitiger Nachricht anzeigen
    if (data && data.msg) {
        alert(data.msg);
        return;
    }

    // HTTP 2xx aber keine/parsbarkeit JSON -> Seite neu laden, damit UI konsistent bleibt
    if (xhr && xhr.status >= 200 && xhr.status < 300) {
        console.warn('Ungueltige oder leere Antwort vom Server trotz 2xx Status.', xhr?.responseText);
        setTimeout(function() { window.location.reload(); }, 80);
        return;
    }

    // Wirklicher Fehler
    const msg = defaultMessage || 'Serverfehler.';
    alert(msg);
}

// Seite nach dem Schließen des Neue-Aufgabe-Modals neu laden
function reloadAfterTaskCreate() {
    if (newTaskModal && typeof newTaskModal.hide === "function") {
        try { newTaskModal.hide(); } catch (e) {}
    }

    // Fallback: eigenes Hide-Utility sorgt für das Entfernen der Backdrops
    hideModal(newTaskModal, "#neuAufgabeModal");

    // Kurzer Delay, damit das Modal optisch verschwinden kann
    setTimeout(function() {
        window.location.reload();
    }, 150);
}


function updateStundenSummary(payload) {
    if (!payload || !payload.success) return;
    const cfg = payload.config || {};
    const usage = payload.usage || {};

    $('#stat-betrieb').text(formatHoursValue(cfg.max_betrieb || 0) + ' Std');
    $('#stat-schule').text(formatHoursValue(cfg.max_berufsschule || 0) + ' Std');
    const totalSpan = $('#stundenLimit');
    if (totalSpan.length) {
        totalSpan.text(formatHoursValue(cfg.max_woche || 0));
    } else {
        $('#stat-gesamt').text(formatHoursValue(cfg.max_woche || 0) + ' Std');
    }

    if (usage.week_start && usage.week_end) {
        $('#stundenWeekLabel').text(formatWeekLabel(usage.week_start, usage.week_end));
    }
}

function refreshStundenSummary() {
    $.getJSON('stunden_config_api.php')
        .done(function(resp) {
            if (resp && resp.success) {
                updateStundenSummary(resp);
            }
        })
        .fail(function() {
            // stillschweigend, damit kein Script-Abbruch
        });
}

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* --------------------------------------------------------------
   formatHoursValue()
   --------------------------------------------------------------
   Nutzt den oben definierten deutschen NumberFormatter, um
   Stundenwerte wie 4 ? "4,00" darzustellen.
---------------------------------------------------------------- */
function formatHoursValue(value) {
    const num = parseFloat(value);
    return hourFormatter.format(isNaN(num) ? 0 : num);
}

/* --------------------------------------------------------------
   formatInputValue()
   --------------------------------------------------------------
   Vereinheitlicht Eingabewerte, bevor sie wieder im Input-Feld
   angezeigt werden.

   Beispiel:
      "4.0000" ? "4"
      "2.34567" ? "2.35"
---------------------------------------------------------------- */
function formatInputValue(value) {
    const num = parseFloat(value);
    if (isNaN(num)) return '';
    return (Math.round(num * 100) / 100).toString();
}

/* --------------------------------------------------------------
   formatWeekLabel()
   --------------------------------------------------------------
   Baut aus zwei ISO-Daten ("2025-01-20") ein schönes deutsches
   Wochenlabel:

      "20.01 - 24.01.2025"
---------------------------------------------------------------- */
function formatWeekLabel(start, end) {
    if (!start || !end) return 'Aktuelle Woche';

    const startDate = new Date(start);
    const endDate   = new Date(end);

    if (isNaN(startDate) || isNaN(endDate)) return 'Aktuelle Woche';

    const startText = startDate.toLocaleDateString('de-DE', { 
        day: '2-digit', month: '2-digit' 
    });

    const endText = endDate.toLocaleDateString('de-DE', { 
        day: '2-digit', month: '2-digit', year: 'numeric' 
    });

    return `${startText} - ${endText}`;
}

/* --------------------------------------------------------------
   formatCategoryLabel()
   --------------------------------------------------------------
   Vereinheitlicht Kategorien für die Anzeige auf Karten und
   im Archiv.

   Intern gespeichert: "betrieb", "unterweisung", "berufsschule"
   Anzeige:            Betrieb, Unterweisung, Berufsschule
---------------------------------------------------------------- */
function formatCategoryLabel(value) {
    const key = (value || '').toString().trim().toLowerCase();
    if (key === 'betrieb')       return 'Betrieb';
    if (key === 'berufsschule')  return 'Berufsschule';
    if (key === 'unterweisung')  return 'Unterweisung';
    if (!key) return '';
    return key.charAt(0).toUpperCase() + key.slice(1);
}

/* --------------------------------------------------------------
   hideModal()
   --------------------------------------------------------------
   Einige Browser/Bootstrap-Versionen behalten Backdrops stehen,
   wenn man Modals sehr schnell öffnet/schließt (z. B. durch AJAX).

   Diese Funktion:
      - schließt das Modal zuverlässig
      - entfernt Backdrops
      - stellt sicher, dass Body wieder scrollbar wird
---------------------------------------------------------------- */
function hideModal(modalInstance, selector) {
    // Bootstrap-intern schließen
    if (modalInstance && typeof modalInstance.hide === 'function') {
        modalInstance.hide();
    }

    // Fallback für den DOM-Zustand
    const $modal = selector
        ? $(selector)
        : (modalInstance && modalInstance._element ? $(modalInstance._element) : $());

    setTimeout(function() {
        if ($modal.length) {
            $modal.removeClass('show')
                  .attr('aria-hidden', 'true');
        }
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }, 120);
}

/* --------------------------------------------------------------
   Workaround für Bootstrap: 
   Entfernt Backdrops nach dem Schließen aller Modals
---------------------------------------------------------------- */
$(document).on('hidden.bs.modal', function() {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open')
             .css('padding-right', '');
});

/* --------------------------------------------------------------
   updateProgressBar()
   --------------------------------------------------------------
   Passt die Breite einer Bootstrap-Progressbar anhand der
   Prozentwerte an.

   - selector  = z. B. "#betriebProgress"
   - used      = bereits verbrauchte Stunden
   - limit     = maximale Stunden (Konfiguration)
---------------------------------------------------------------- */
function updateProgressBar(selector, used, limit) {
    const percentage = limit > 0 ? Math.min(100, (used / limit) * 100) : 0;
    $(selector).css('width', `${percentage}%`);
}

/* --------------------------------------------------------------
   buildHoursHtml()
   --------------------------------------------------------------
   Baut die 4,00 Std : Betrieb-Anzeige für die Task-Karten.

   ? Rückgabe ist HTML-Code, der direkt in die Karte eingesetzt
     werden kann.
---------------------------------------------------------------- */
function buildHoursHtml(hours, type) {
    const num = parseFloat(hours);
    if (!num || num <= 0) return '';

    const label = type === 'berufsschule' ? 'Berufsschule' : 'Betrieb';
    return `
        <div class="task-hours small fw-semibold mt-1">
            ${formatHoursValue(num)} Std : ${label}
        </div>`;
}


/* ==========================================================================
   KARTENBAU, ARCHIV UND UI-UPDATES
   --------------------------------------------------------------------------
   Dieser Block ist das Herzstück des dynamischen UI-Verhaltens.
   Hier wird entschieden:
     - Wie eine Karte (Task) gebaut wird
     - Wie Archiv-Einträge aussehen
     - Wie Aufgaben optisch aktualisiert werden
     - Wie Aufgaben zwischen Spalten verschoben werden
   ========================================================================== */


/* --------------------------------------------------------------
   buildCardMarkup(task)
   --------------------------------------------------------------
   Baut das komplette HTML einer Kanban-Karte als String.

   ? Wird genutzt:
       - Beim Erstellen einer neuen Aufgabe
       - Beim Wiederherstellen aus dem Archiv
       - Wenn ein AJAX-Fallback greift
---------------------------------------------------------------- */
function buildCardMarkup(task) {

    // Stundentext, falls vorhanden
    const hoursHtml = buildHoursHtml(task.stunden, task.stunden_typ);

    // Lesbares Label (Betrieb / Unterweisung / ...)
    const categoryLabel = formatCategoryLabel(task.kategorie);

    // Anzeige fuer Status-Badge
    const statusText = escapeHtml(task.status_label || task.status || '');

    return `
        <div class="kanban-card"
             data-id="${task.id}"
             data-titel="${escapeHtml(task.titel || '')}"
             data-beschreibung="${escapeHtml(task.beschreibung || '')}"
             data-kategorie="${escapeHtml(task.kategorie || '')}"
             data-status="${escapeHtml(task.status || '')}"
             data-stunden="${escapeHtml(task.stunden ?? '')}"
             data-stunden_typ="${escapeHtml(task.stunden_typ ?? '')}"
             draggable="true">

            <div class="task-left">
                <strong class="task-title">
                    ${escapeHtml(task.titel || '')}
                </strong>

                <p>${escapeHtml(task.beschreibung || '')}</p>

                <small class="text-muted">${escapeHtml(categoryLabel)}</small>

                ${hoursHtml}
            </div>

            <div class="task-right task-actions">

                <button type="button" class="editButton edit-task" aria-label="Aufgabe bearbeiten">
                    <svg viewBox="0 0 24 24" class="pencil">
                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25z" fill="#B5BAC1"></path>
                        <path d="M20.71 7.04a1.004 1.004 0 000-1.42l-2.34-2.34a1.004 1.004 0 00-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z" fill="#B5BAC1"></path>
                    </svg>
                    <span class="tooltip">Bearbeiten</span>
                </button>

                <button type="button" class="archive-task archive-task-btn" aria-label="Aufgabe archivieren">
                    <svg viewBox="0 0 24 24" class="archive-icon" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span class="tooltip">Archivieren</span>
                </button>

                <button type="button" class="deleteButton delete-task" aria-label="Aufgabe löschen">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 50 59" class="bin">
                        <path fill="#B5BAC1" d="M0 7.5C0 5.01472 2.01472 3 4.5 3H45.5C47.9853 3 50 5.01472 50 7.5V7.5C50 8.32843 49.3284 9 48.5 9H1.5C0.671571 9 0 8.32843 0 7.5V7.5Z"></path>
                        <path fill="#B5BAC1" d="M17 3C17 1.34315 18.3431 0 20 0H29.3125C30.9694 0 32.3125 1.34315 32.3125 3V3H17V3Z"></path>
                        <path fill="#B5BAC1" d="M2.18565 18.0974C2.08466 15.821 3.903 13.9202 6.18172 13.9202H43.8189C46.0976 13.9202 47.916 15.821 47.815 18.0975L46.1699 55.1775C46.0751 57.3155 44.314 59.0002 42.1739 59.0002H7.8268C5.68661 59.0002 3.92559 57.3155 3.83073 55.1775L2.18565 18.0974ZM18.0003 49.5402C16.6196 49.5402 15.5003 48.4209 15.5003 47.0402V24.9602C15.5003 23.5795 16.6196 22.4602 18.0003 22.4602C19.381 22.4602 20.5003 23.5795 20.5003 24.9602V47.0402C20.5003 48.4209 19.381 49.5402 18.0003 49.5402ZM29.5003 47.0402C29.5003 48.4209 30.6196 49.5402 32.0003 49.5402C33.381 49.5402 34.5003 48.4209 34.5003 47.0402V24.9602C34.5003 23.5795 33.381 22.4602 32.0003 22.4602C30.6196 22.4602 29.5003 23.5795 29.5003 24.9602V47.0402Z"></path>
                        <path fill="#B5BAC1" d="M2 13H48L47.6742 21.28H2.32031L2 13Z"></path>
                    </svg>
                    <span class="tooltip">Löschen</span>
                </button>
            </div>

            <span class="badge-status badge-${escapeHtml(task.status || '')}">
                ${statusText}
            </span>
        </div>
    `;
}


/* --------------------------------------------------------------
   addCardBelowHeader(columnElement, markup)
   --------------------------------------------------------------
   Fügt eine neu erstellte Karte *direkt unterhalb* des Spalten-
   Titels ein.

   Dadurch bleiben neue Karten immer ganz oben sichtbar.
---------------------------------------------------------------- */
function addCardBelowHeader($column, cardMarkup) {
    if (!$column || !$column.length) return;

    const $cardEl = $(cardMarkup);
    const $header = $column.children('h4').first();

    if ($header.length) {
        // direkt unter H4 einfügen
        $cardEl.insertAfter($header);
    } else {
        // falls keine Ãœberschrift gefunden wird, oben einfügen
        $column.prepend($cardEl);
    }
}


/* --------------------------------------------------------------
   formatArchiveDate()
   --------------------------------------------------------------
   Konvertiert Server-Datum in:
       "24.01.2025, 14:22"
---------------------------------------------------------------- */
function formatArchiveDate(value) {
    if (!value) return new Date().toLocaleString('de-DE');

    const normalized = value.replace(' ', 'T'); // manche Server liefern kein "T"
    const date = new Date(normalized);

    if (isNaN(date)) return value;
    return date.toLocaleString('de-DE', { 
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}


/* --------------------------------------------------------------
   appendArchiveEntry(entry)
   --------------------------------------------------------------
   Baut einen neuen Eintrag fürs Archiv und setzt ihn GANZ OBEN ein.

   entry = {
       id, title, category, archived_at, status
   }
---------------------------------------------------------------- */
function appendArchiveEntry(entry) {

    const categoryText = formatCategoryLabel(entry.category || '-');

    const html = `
        <div class="archive-entry" data-archive-id="${entry.id || ''}">
            
            <div class="d-flex justify-content-between align-items-center">

                <strong>${escapeHtml(entry.title || '')}</strong>

                <div class="archive-actions">
                    <span class="badge bg-light text-dark">${escapeHtml(categoryText)}</span>

                    <!-- Wiederherstellen -->
                    <button type="button" class="archive-restore-btn" aria-label="Aufgabe wiederherstellen">
                        <svg class="restore-icon"
                             xmlns="http://www.w3.org/2000/svg" 
                             viewBox="0 0 24 24" fill="none" 
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 14 4 9 9 4"></polyline>
                            <path d="M20 20v-5a7 7 0 0 0-7-7H4"></path>
                        </svg>
                        <span class="tooltip">Wiederherstellen</span>
                    </button>
                </div>

            </div>

            <div class="meta">
                Beendet am ${escapeHtml(formatArchiveDate(entry.archived_at))}
            </div>
        </div>
    `;

    archiveList.prepend(html);
    archiveEmpty.addClass('d-none');
}



/* --------------------------------------------------------------
   updateArchiveEmptyState()
   --------------------------------------------------------------
   Prüft, ob das Archiv leer ist und zeigt/versteckt den Hinweis.
---------------------------------------------------------------- */
function updateArchiveEmptyState() {
    const hasEntries = archiveList.children('.archive-entry').length > 0;
    archiveEmpty.toggleClass('d-none', hasEntries);
}


/* --------------------------------------------------------------
   applyCardUpdates($card, task)
   --------------------------------------------------------------
   Aktualisiert ALLE Daten einer vorhandenen Karte:

   - Titel
   - Beschreibung
   - Kategorie
   - Badge
   - Stundenanzeige
   - data-Attribute (damit bearbeiten funktioniert)
---------------------------------------------------------------- */
function applyCardUpdates($card, task) {

    /* Daten-Attribute aktualisieren */
    $card.attr({
        'data-titel': task.titel || '',
        'data-beschreibung': task.beschreibung || '',
        'data-kategorie': task.kategorie || '',
        'data-status': task.status || '',
        'data-stunden': task.stunden ?? '',
        'data-stunden_typ': task.stunden_typ ?? ''
    }).data({
        titel: task.titel || '',
        beschreibung: task.beschreibung || '',
        kategorie: task.kategorie || '',
        status: task.status || '',
        stunden: task.stunden ?? '',
        stunden_typ: task.stunden_typ ?? ''
    });

    /* Sichtbare Inhalte aktualisieren */
    const categoryLabel = formatCategoryLabel(task.kategorie);
    const badgeText     = (task.status_label || task.status || '').toString();

    $card.find('strong').first().text(task.titel || '');
    $card.find('p').first().text(task.beschreibung || '');
    $card.find('small.text-muted').first().text(categoryLabel);

    /* Badge-Klasse aktualisieren */
    $card.find('.badge-status')
        .removeClass('badge-offen badge-in_bearbeitung badge-fertig')
        .addClass('badge-' + task.status)
        .text(badgeText);

    /* Stundenanzeige aktualisieren (immer in der linken Spalte) */
    const hoursHtml = buildHoursHtml(task.stunden, task.stunden_typ);
    const leftCol = $card.find('.task-left').first();
    const existingHours = leftCol.find('.task-hours').first();

    if (hoursHtml) {
        existingHours.length
            ? existingHours.replaceWith($(hoursHtml))
            : leftCol.append($(hoursHtml));
    } else {
        existingHours.remove();
    }
}


/* --------------------------------------------------------------
   moveCardToStatus($card, status)
   --------------------------------------------------------------
   Verschiebt eine Karte in die passende Kanban-Spalte.
   Mit animiertem Fade-Out/Fade-In für bessere UI-Optik.
---------------------------------------------------------------- */
function moveCardToStatus($card, status) {
    const column = $(`.kanban-column[data-status='${status}']`);
    if (column.length) {
        $card.fadeOut(150, function() {
            $card.appendTo(column).fadeIn(200);
        });
    }
}


/* --------------------------------------------------------------
   EDIT-FORMULAR MIT DATEN DER ANGEKLICKTEN KARTE BEFÜLLEN
   ----------------------------------------------------------------
   - Wird aufgerufen, wenn der Bearbeiten-Button einer Karte geklickt wird
   - Liest alle data-* Attribute aus der Karte und übertrügt sie
     in die Felder des Bearbeiten-Modals
---------------------------------------------------------------- */
function populateEditForm($card) {
    // ID der Aufgabe in verstecktes Feld schreiben
    $('#editAufgabeId').val($card.data('id'));

    // Titel & Beschreibung
    $('#editTitel').val($card.data('titel') || '');
    $('#editBeschreibung').val($card.data('beschreibung') || '');

    // Kategorie (Standard: 'betrieb', falls leer)
    const kat = $card.data('kategorie') || 'betrieb';
    editCategorySelect.val(kat);

    // Stunden-Werte in beide Felder kopieren
    // (optional-Stunden und Schulungs-Stunden)
    const stundenVal = $card.data('stunden') || '';
    editOptionalStundenInput.val(stundenVal);
    editStundenInput.val(stundenVal);

    // Stunden-Typ (von wo abziehen?)  default 'betrieb'
    editStundenTypSelect.val($card.data('stunden_typ') || 'betrieb');

    // Je nach Kategorie: Schulungsfelder sichtbar/unsichtbar
    toggleEditTrainingFields(kat);

    // Status (offen / in Bearbeitung / fertig)
    editStatusSelect.val($card.data('status') || 'offen');
}


/* --------------------------------------------------------------
   INITIAL: TRAINING-FELDER (NEUE AUFGABE)
   - beim Laden die aktuelle Auswahl auswerten
   - bei Ã„nderung der Kategorie erneut schalten
---------------------------------------------------------------- */
// ----------------------------------------------
// Trainings-Felder für Neue Aufgabe
// ----------------------------------------------
function toggleTrainingFields(category) {
    if (category === 'unterweisung') {
        $('#optionalHoursGroup').addClass('d-none');
        $('#trainingFields').removeClass('d-none');

        $('#trainingStunden').prop('disabled', false);
        $('#trainingStundenTyp').prop('disabled', false);
    } else {
        $('#optionalHoursGroup').removeClass('d-none');
        $('#trainingFields').addClass('d-none');

        $('#trainingStunden').prop('disabled', true);
        $('#trainingStundenTyp').prop('disabled', true);
    }
}

// ----------------------------------------------
// Trainings-Felder für Aufgabe bearbeiten
// ----------------------------------------------
function toggleEditTrainingFields(category) {
    if (category === 'unterweisung') {
        $('#editOptionalHoursGroup').addClass('d-none');
        $('#editTrainingFields').removeClass('d-none');

        $('#editStunden').prop('disabled', false);
        $('#editStundenTyp').prop('disabled', false);
    } else {
        $('#editOptionalHoursGroup').removeClass('d-none');
        $('#editTrainingFields').addClass('d-none');

        $('#editStunden').prop('disabled', true);
        $('#editStundenTyp').prop('disabled', true);
    }
}


toggleTrainingFields(categorySelect.val());

categorySelect.on('change', function() {
    toggleTrainingFields($(this).val());
});


/* --------------------------------------------------------------
   INITIAL: TRAINING-FELDER (EDITIEREN)
   - beim öffnen des Bearbeiten-Modals
   - und bei Ã„nderung der Kategorie im Edit-Modal
---------------------------------------------------------------- */
toggleEditTrainingFields(editCategorySelect.val());

editCategorySelect.on('change', function() {
    toggleEditTrainingFields($(this).val());
});


/* --------------------------------------------------------------
   FORMULAR: STUNDEN-KONFIG (MAX-WOCHE, BETRIEB, SCHULE)
   ----------------------------------------------------------------
   - Speichert Einstellungen über stunden_config_api.php
   - Bei Erfolg: UI aktualisieren + Erfolgsmeldung + Modal schließen
   - Bei Fehler: Fehlermeldung anzeigen
---------------------------------------------------------------- */
$('#stundenConfigForm').on('submit', function(e) {
    e.preventDefault();

    const feedback = $('#stundenConfigFeedback');

    // Feedback-Bereich immer zuerst leeren & verstecken
    feedback
        .addClass('d-none')
        .removeClass('alert-success alert-danger')
        .text('');

    $.post(
        'stunden_config_api.php',
        $(this).serialize(),
        function(resp) {
            if (resp && resp.success) {
                // Neue Werte ins Summary schreiben
                updateStundenSummary(resp);

                feedback
                    .removeClass('d-none')
                    .addClass('alert-success')
                    .text('Einstellungen gespeichert.');

                // Modal nach kurzer Zeit schließen
                setTimeout(function() {
                    hideModal(stundenConfigModal, '#stundenConfigModal');
                }, 900);
            } else {
                const message =
                    resp && resp.msg
                        ? resp.msg
                        : 'Speichern nicht möglich.';

                feedback
                    .removeClass('d-none')
                    .addClass('alert-danger')
                    .text(message);
            }
        },
        'json'
    ).fail(function() {
        feedback
            .removeClass('d-none')
            .addClass('alert-danger')
            .text('Serverfehler beim Speichern.');
    });
});


/* --------------------------------------------------------------
   RESET BEI SCHLIESSEN DES STUNDEN-KONFIG-MODALS
   - Feedback wird geleert
---------------------------------------------------------------- */
$('#stundenConfigModal').on('hidden.bs.modal', function() {
    $('#stundenConfigFeedback')
        .addClass('d-none')
        .removeClass('alert-success alert-danger')
        .text('');
});


/* ==========================================================================
   DRAG & DROP (START / ENDE / DROP-ZONE)
   --------------------------------------------------------------------------
   Diese drei Listener sorgen für:
     - Karte kann gezogen werden
     - visuelles Markieren der "dragging" Karte
     - Zielspalten akzeptieren Drop-Vorgänge
   ========================================================================== */

// Drag starten: merken, welche Karte gerade gezogen wird
$(document).on('dragstart', '.kanban-card', function(e) {
    draggedCard = this;
    $(this).addClass('dragging');

    // Für Firefox: irgendeinen Text setzen, sonst kein Drag & Drop
    e.originalEvent.dataTransfer.setData('text', '');
});

// Drag beendet: Optik wieder normal, Referenz löschen
$(document).on('dragend', '.kanban-card', function() {
    $(this).removeClass('dragging');
    draggedCard = null;
});

// Spalten müssen den Drop zulassen ? preventDefault()
$('.kanban-column').on('dragover', function(e) {
    e.preventDefault();
});


/* ==========================================================================
   DROP-EVENT: KARTE IN ANDERE SPALTE ZIEHEN
   --------------------------------------------------------------------------
   - UI sofort aktualisieren (optimistic UI)
   - Server per AJAX über neuen Status informieren
   - Reaktion:
        -ggf. Archivieren
        -sonst Karte mit neuen Statusdaten updaten
        -Stunden-Ãœbersicht aktualisieren
   ========================================================================== */
$('.kanban-column').on('drop', function() {
    if (!draggedCard) return;

    const $targetColumn = $(this);
    const newStatus     = $targetColumn.data('status');     // offen / in_bearbeitung / fertig
    const $card         = $(draggedCard);
    const taskId        = $card.data('id');

    // Sofortige Bewegung im UI (für flüssiges Feeling)
    $card.fadeOut(150, function() {
        $card.appendTo($targetColumn);
        $card.fadeIn(200);
    });

    // Server über Statuswechsel informieren
    $.ajax({
        url: 'aufgaben_update.php',
        type: 'POST',
        dataType: 'json',
        data: {
            id: taskId,
            status: newStatus,

            /* Wenn Status "in_bearbeitung":
               - Kann serverseitig genutzt werden, um automatisch
                 einen Wochenbericht anzulegen/einzutragen. */
            auto_wochenbericht: newStatus === 'in_bearbeitung' ? 1 : 0
        }
    }).done(function(response) {
        if (response && response.success) {

            // Fall: Server sagt, Aufgabe wurde archiviert
            if (response.archiviert) {
                const title    = $card.find('strong').text().trim();
                const category = $card.find('small.text-muted').text().trim();

                $card.fadeOut(150, function() {
                    $(this).remove();
                });

                appendArchiveEntry({
                    id: response.id,
                    title: title,
                    category: category,
                    archived_at: response.archiviert_am,
                    status: response.status
                });

            } else {
                // Nur Status / Label / Stunden aktualisieren
                applyCardUpdates($card, {
                    titel: $card.data('titel'),
                    beschreibung: $card.data('beschreibung'),
                    kategorie: $card.data('kategorie'),
                    status: newStatus,
                    status_label: response.status_label || '',
                    stunden: $card.data('stunden'),
                    stunden_typ: $card.data('stunden_typ')
                });
            }

            // Stunden-Ãœbersicht oben aktualisieren
            refreshStundenSummary();

        } else if (response && response.msg) {
            alert(response.msg);
        } else {
            alert('Aufgabe konnte nicht aktualisiert werden (unerwartete Antwort).');
        }

    }).fail(function(xhr) {

        // Fallback: Versuchen, trotz HTTP-Fehler evtl. JSON-Erfolg auszuwerten
        handleAjaxFailWithSuccessFallback(
            xhr,
            function(data) {

                const archiveFlag = data.archiviert;

                if (archiveFlag) {
                    const title    = $card.find('strong').text().trim();
                    const category = $card.find('small.text-muted').text().trim();

                    $card.fadeOut(150, function() {
                        $(this).remove();
                    });

                    appendArchiveEntry({
                        id: data.id,
                        title: title,
                        category: category,
                        archived_at: data.archiviert_am,
                        status: data.status
                    });

                } else {
                    const statusLabel = data.status_label || data.status || newStatus;

                    applyCardUpdates($card, {
                        titel: $card.data('titel'),
                        beschreibung: $card.data('beschreibung'),
                        kategorie: $card.data('kategorie'),
                        status: data.status || newStatus,
                        status_label: statusLabel,
                        stunden: $card.data('stunden'),
                        stunden_typ: $card.data('stunden_typ')
                    });
                }

                refreshStundenSummary();
            },
            ''
        );
    });
});


/* ==========================================================================
   ARCHIV: AUFGABE WIEDERHERSTELLEN
   --------------------------------------------------------------------------
   - Klick auf "Wiederherstellen" bei einer Archiv-Eintrag
   - Sendet restore_id an aufgaben_create.php
   - Entfernt Eintrag aus Archivliste
   - Fügt Karte wieder im passenden Status im Board ein
   ========================================================================== */
$(document).on('click', '.archive-restore-btn', function(e) {
    e.preventDefault();

    const btn   = $(this);
    const entry = btn.closest('.archive-entry');
    const id    = parseInt(entry.data('archive-id'), 10);

    if (!id) return;

    // Doppelklick-Schutz
    btn.prop('disabled', true);

    $.ajax({
        url: 'aufgaben_create.php',
        type: 'POST',
        data: { restore_id: id },
        dataType: 'json'
    }).done(function(resp) {

        if (resp && resp.success) {

            // Archiv-Eintrag ausblenden/entfernen
            entry.fadeOut(150, function() {
                $(this).remove();
                updateArchiveEmptyState();
            });

            // Status bestimmen (Standard: offen)
            const status = resp.status || 'offen';
            resp.status_label = resp.status_label || status;

            // Karte wieder im Board einfügen (unter Ãœberschrift)
            const statusCol = $(`.kanban-column[data-status='${status}']`);
            addCardBelowHeader(statusCol, buildCardMarkup(resp));

        } else {
            const message =
                resp && resp.msg
                    ? resp.msg
                    : 'Aufgabe konnte nicht wiederhergestellt werden.';
            alert(message);
            btn.prop('disabled', false);
        }

    }).fail(function(xhr) {

        // Fallback: auch hier Versuch, JSON-Erfolg zu lesen
        handleAjaxFailWithSuccessFallback(
            xhr,
            function(data) {

                entry.fadeOut(150, function() {
                    $(this).remove();
                    updateArchiveEmptyState();
                });

                const status = data.status || 'offen';
                data.status_label = data.status_label || status;

                const statusCol = $(`.kanban-column[data-status='${status}']`);
                addCardBelowHeader(statusCol, buildCardMarkup(data));
            },
            ''
        );

        btn.prop('disabled', false);
    });
});


/* ==========================================================================
   FORMULAR: NEUE AUFGABE
   --------------------------------------------------------------------------
   - Wird abgeschickt, sobald im "Neue Aufgabe"-Modal auf Speichern geklickt wird
   - Schickt alle Felder an aufgaben_create.php
   - Reaktion:
       - evtl. direkt archiviert ? Archivliste ergänzen
       - sonst in passende Kanban-Spalte einfügen
       - Formular zurücksetzen
       - Stundenübersicht aktualisieren, wenn Status != "offen"
   ========================================================================== */
$('#aufgabeForm').submit(function(e) {
    e.preventDefault();

    const formData = $(this).serialize();

    $.ajax({
        url: 'aufgaben_create.php',
        type: 'POST',
        dataType: 'json',
        data: formData
    }).done(function(resp) {

        if (resp && resp.success) {

            // Modal schließen + nach Schließen reloaden
            reloadAfterTaskCreate();

            if (resp.archiviert) {
                // Aufgabe ist direkt im Archiv gelandet
                appendArchiveEntry({
                    id: resp.id,
                    title: resp.titel,
                    category: resp.kategorie,
                    archived_at: resp.archiviert_am,
                    status: resp.status
                });
            } else {
                // Aufgabe direkt in die entsprechende Spalte packen
                const statusCol = $(`.kanban-column[data-status='${resp.status}']`);
                resp.status_label = resp.status_label || resp.status;
                addCardBelowHeader(statusCol, buildCardMarkup(resp));
            }

            // Formular auf Ausgangszustand zurücksetzen
            $('#aufgabeForm')[0].reset();
            categorySelect.val('betrieb');
            toggleTrainingFields('betrieb');

            // Wenn nicht "offen": Stundenübersicht neu laden
            if (resp.status !== 'offen') {
                refreshStundenSummary();
            }

        } else {
            const message =
                resp && resp.msg
                    ? resp.msg
                    : 'Aufgabe konnte nicht gespeichert werden.';
            alert(message);
        }

    }).fail(function(xhr) {

        // Fallback: JSON aus Fehlantwort versuchen zu lesen
        handleAjaxFailWithSuccessFallback(
            xhr,
            function(resp) {
                // Modal schließen + nach Schließen reloaden
                reloadAfterTaskCreate();

                if (resp.archiviert) {
                    appendArchiveEntry({
                        id: resp.id,
                        title: resp.titel,
                        category: resp.kategorie,
                        archived_at: resp.archiviert_am,
                        status: resp.status
                    });
                } else {
                    const statusCol = $(`.kanban-column[data-status='${resp.status}']`);
                    resp.status_label = resp.status_label || resp.status;
                    addCardBelowHeader(statusCol, buildCardMarkup(resp));
                }

                $('#aufgabeForm')[0].reset();
                categorySelect.val('betrieb');
                toggleTrainingFields('betrieb');

                if (resp.status !== 'offen') {
                    refreshStundenSummary();
                }

            },
            ''
        );
    });
});


/* ==========================================================================
   KLICK AUF EDIT-BUTTON EINER KARTE
   --------------------------------------------------------------------------
   - Merkt sich die Karte (editCardRef)
   - Befüllt das Bearbeiten-Modal mit populateEditForm()
   - Öffnet das Modal
   ========================================================================== */
$(document).on('click', '.edit-task', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const card = $(this).closest('.kanban-card');

    // Referenz für späteres Update merken
    editCardRef = card;

    // Formular mit Daten der Karte füllen
    populateEditForm(card);

    // Modal anzeigen
    if (editModal) {
        editModal.show();
    }
});


/* ==========================================================================
   FORMULAR: AUFGABE BEARBEITEN
   --------------------------------------------------------------------------
   - Schickt neue Werte an aufgaben_edit.php
   - Je nach Antwort:
       - Aufgabe archivieren und ins Archiv verschieben
       - oder Karte im Board aktualisieren (Status / Text / Stunden)
       - Stundenübersicht neu laden
   ========================================================================== */
editForm.on('submit', function(e) {
    e.preventDefault();
    if (!editCardRef) return;

    const formData = $(this).serialize();

    $.ajax({
        url: 'aufgaben_edit.php',
        type: 'POST',
        dataType: 'json',
        data: formData
    }).done(function(resp) {

        if (resp && resp.success) {

            hideModal(editModal, '#editAufgabeModal');

            if (resp.archiviert) {
                // Karte aus Board entfernen und ins Archiv
                editCardRef.fadeOut(150, function() { $(this).remove(); });

                appendArchiveEntry({
                    id: resp.id,
                    title: resp.titel,
                    category: resp.kategorie,
                    archived_at: resp.archiviert_am,
                    status: resp.status
                });

            } else {
                // Nur Daten der Karte aktualisieren und ggf. in andere Spalte verschieben
                resp.status_label = resp.status_label || resp.status;
                applyCardUpdates(editCardRef, resp);
                moveCardToStatus(editCardRef, resp.status);
            }

            editCardRef = null;
            refreshStundenSummary();

        } else {
            const message =
                resp && resp.msg
                    ? resp.msg
                    : 'Aufgabe konnte nicht aktualisiert werden.';
            alert(message);
        }

    }).fail(function(xhr) {

        // Fallback ? JSON aus der Fehlerantwort nutzen, falls vorhanden
        handleAjaxFailWithSuccessFallback(
            xhr,
            function(data) {

                hideModal(editModal, '#editAufgabeModal');

                if (data.archiviert) {
                    editCardRef.fadeOut(150, function() { $(this).remove(); });

                    appendArchiveEntry({
                        id: data.id,
                        title: data.titel,
                        category: data.kategorie,
                        archived_at: data.archiviert_am,
                        status: data.status
                    });

                } else {
                    const statusLabel = data.status_label || data.status;

                    applyCardUpdates(editCardRef, {
                        titel: data.titel,
                        beschreibung: data.beschreibung,
                        kategorie: data.kategorie,
                        status: data.status,
                        status_label: statusLabel,
                        stunden: data.stunden,
                        stunden_typ: data.stunden_typ
                    });

                    moveCardToStatus(editCardRef, data.status);
                }

                editCardRef = null;
                refreshStundenSummary();
            },
            ''
        );
    });
});


/* ==========================================================================
   EXPLIZIT ARCHIVIEREN (Archiv-Button auf einer Karte)
   --------------------------------------------------------------------------
   - Fragt zur Sicherheit nach (confirm)
   - Sendet id an archive_aufgabe.php
   - Entfernt Karte aus Board, setzt neuen Archiv-Eintrag
   - Aktualisiert Archiv-Leer-Status & Stundenübersicht
   ========================================================================== */
$(document).on('click', '.archive-task', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const card = $(this).closest('.kanban-card');
    const id   = card.data('id');
    if (!id) return;

    if (!confirm('Aufgabe archivieren?')) return;

    $.ajax({
        url: 'archive_aufgabe.php',
        type: 'POST',
        dataType: 'json',
        data: { id }
    }).done(function(resp) {

        if (resp && resp.success) {

            card.fadeOut(150, function() { $(this).remove(); });

            appendArchiveEntry({
                id: resp.id,
                title: resp.titel,
                category: resp.kategorie,
                archived_at: resp.archiviert_am,
                status: resp.status
            });

            updateArchiveEmptyState();
            refreshStundenSummary();

        } else {
            const message =
                resp && resp.msg
                    ? resp.msg
                    : 'Aufgabe konnte nicht archiviert werden.';
            alert(message);
        }

    }).fail(function(xhr) {

        handleAjaxFailWithSuccessFallback(
            xhr,
            function(data) {
                card.fadeOut(150, function() { $(this).remove(); });

                appendArchiveEntry({
                    id: data.id,
                    title: data.titel,
                    category: data.kategorie,
                    archived_at: data.archiviert_am,
                    status: data.status
                });

                updateArchiveEmptyState();
                refreshStundenSummary();
            },
            'Aufgabe konnte nicht archiviert werden. Serverantwort: '
        );
    });
});


/* ==========================================================================
   AUFGABE LÃ–SCHEN
   --------------------------------------------------------------------------
   - Klick auf Mülleimer ? confirm()
   - Sendet id an aufgaben_delete.php
   - Entfernt Karte bei Erfolg
   - Aktualisiert Stundenübersicht
   ========================================================================== */
$(document).on('click', '.delete-task', function(e) {
    e.preventDefault();
    e.stopPropagation();

    const card = $(this).closest('.kanban-card');

    if (!confirm('Aufgabe wirklich löschen?')) return;

    $.ajax({
        url: 'aufgaben_delete.php',
        type: 'POST',
        dataType: 'json',
        data: { id: card.data('id') }
    }).done(function(resp) {

        if (resp && resp.success) {
            card.fadeOut(150, function() { $(this).remove(); });
            refreshStundenSummary();

        } else {
            const message =
                resp && resp.msg
                    ? resp.msg
                    : 'Aufgabe konnte nicht gelöscht werden.';
            alert(message);
        }

    }).fail(function(xhr) {


    });
});


/* ==========================================================================
   INITIALER AUFRUF BEIM LADEN DER SEITE
   --------------------------------------------------------------------------
   - Holt aktuelle Stunden-Kontingente & bereits verbrauchte Zeiten
   - Setzt die Anzeige oben (Aktuelle Woche / Betrieb / Schule / Gesamt)
   ========================================================================== */
refreshStundenSummary();

})(); // Ende IIFE
</script>

<?php
// Finale Übergabe an das Layout-Template
$page_content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
