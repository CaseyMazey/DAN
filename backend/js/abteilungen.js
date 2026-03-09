document.addEventListener('DOMContentLoaded', () => {

    const preset = document.getElementById('depPresetSelect');
    const input  = document.getElementById('depNameInput');

    if (!preset || !input) return;

    preset.addEventListener('change', () => {
        input.value = preset.value;
        input.focus();
    });
});
// ------------------------------------------------------------------
// JAVASCRIPT: Dropdown "Vorhandene Abteilungen" → Input-Feld übernehmen
// ------------------------------------------------------------------
// Zweck: Wenn man eine bereits existierende Abteilung auswählt,
// wird dieser Text automatisch in das Eingabefeld eingetragen.
// Hilft, um Tippfehler zu vermeiden und schneller neue Abteilungen
// mit ähnlichen Namen anzulegen.