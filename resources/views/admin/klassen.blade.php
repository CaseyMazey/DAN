<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
:root {
    --p-bg:#F4F7FB;--p-surface:#fff;--p-border:#E2E8F0;--p-text:#1A2233;--p-muted:#64748B;--p-subtle:#94A3B8;
    --p-blue:#2563EB;--p-blue-bg:#EEF3FF;--p-green:#16A34A;--p-green-bg:#DCFCE7;
    --p-amber:#D97706;--p-amber-bg:#FEF3C7;--p-red:#DC2626;--p-red-bg:#FEE2E2;
    --p-radius:12px;--p-rsm:8px;--p-font:'DM Sans',system-ui,sans-serif;
}
html[data-theme="dark"] {
    --p-bg:#0D1117;--p-surface:#161B22;--p-border:#30363D;--p-text:#E6EDF3;--p-muted:#8B949E;--p-subtle:#484F58;
    --p-blue:#58A6FF;--p-blue-bg:#0D2340;--p-green:#3FB950;--p-green-bg:#0A2918;
    --p-amber:#E3B341;--p-amber-bg:#2D1F00;--p-red:#F85149;--p-red-bg:#2D0A0A;
}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}
.page{max-width:1100px;margin:0 auto;padding:28px 20px 48px;display:flex;flex-direction:column;gap:20px}
.page-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.page-title{font-size:21px;font-weight:700;letter-spacing:-.4px}
.page-sub{font-size:13px;color:var(--p-muted);margin-top:3px}

.flash{padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid}
.flash.ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash.err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius)}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border);border-radius:var(--p-radius) var(--p-radius) 0 0}
.card-title{font-size:14px;font-weight:600;color:var(--p-text)}
.card-body{padding:20px}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--p-rsm);font-size:13.5px;font-weight:500;font-family:var(--p-font);cursor:pointer;border:none;transition:opacity .15s;text-decoration:none}
.btn:hover{opacity:.85}
.btn-blue{background:var(--p-blue);color:#fff}
.btn-gray{background:var(--p-border);color:var(--p-text)}
.btn-sm{padding:5px 12px;font-size:12.5px}

.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%}
.fi:focus{border-color:var(--p-blue)}

.pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}

.action-btn{font-size:12.5px;font-weight:500;padding:5px 10px;border-radius:var(--p-rsm);border:none;cursor:pointer;font-family:var(--p-font);transition:opacity .15s}
.action-btn:hover{opacity:.8}
.action-edit{background:var(--p-border);color:var(--p-text)}
.action-del{background:var(--p-red-bg);color:var(--p-red)}

.klasse-card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius)}
.klasse-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.klasse-name{font-size:16px;font-weight:700;letter-spacing:-.3px}
.klasse-meta{font-size:12px;color:var(--p-muted);margin-top:3px;display:flex;gap:10px;flex-wrap:wrap}
.klasse-body{padding:20px;display:flex;flex-direction:column;gap:16px}

.section-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);margin-bottom:8px}

.tag-list{display:flex;flex-wrap:wrap;gap:6px}
.tag{background:var(--p-bg);border:1px solid var(--p-border);border-radius:99px;padding:3px 10px;font-size:12.5px;color:var(--p-muted)}

/* Multi-Combobox Tags */
.mcb-wrap{position:relative}
.mcb-field{
    display:flex;flex-wrap:wrap;gap:5px;align-items:center;
    min-height:40px;padding:5px 32px 5px 8px;
    border:1px solid var(--p-border);border-radius:var(--p-rsm);
    background:var(--p-bg);cursor:text;transition:border-color .12s;
}
.mcb-field:focus-within{border-color:var(--p-blue)}
.mcb-tag{
    display:inline-flex;align-items:center;gap:4px;
    background:var(--p-blue-bg);color:var(--p-blue);
    border:1px solid var(--p-blue);border-radius:99px;
    padding:2px 8px;font-size:12px;font-weight:500;white-space:nowrap;
}
.mcb-tag-remove{
    background:none;border:none;cursor:pointer;
    color:var(--p-blue);font-size:14px;line-height:1;padding:0;
    opacity:.7;
}
.mcb-tag-remove:hover{opacity:1}
.mcb-input{
    border:none;background:transparent;outline:none;
    font-family:var(--p-font);font-size:13.5px;color:var(--p-text);
    min-width:120px;flex:1;padding:2px 4px;
}
.mcb-input::placeholder{color:var(--p-subtle)}
.mcb-chevron{
    position:absolute;right:10px;top:50%;transform:translateY(-50%);
    pointer-events:none;color:var(--p-muted);transition:transform .15s;
}
.mcb-wrap.open .mcb-chevron{transform:translateY(-50%) rotate(180deg)}
.mcb-dropdown{
    position:absolute;top:calc(100% + 4px);left:0;right:0;
    background:var(--p-surface);border:1px solid var(--p-border);
    border-radius:var(--p-rsm);box-shadow:0 8px 24px rgba(0,0,0,.12);
    z-index:200;max-height:200px;overflow-y:auto;display:none;
}
.mcb-wrap.open .mcb-dropdown{display:block}
.mcb-option{
    display:flex;align-items:center;gap:8px;
    padding:8px 12px;font-size:13.5px;cursor:pointer;
    color:var(--p-text);transition:background .1s;
}
.mcb-option:hover,.mcb-option.focused{background:var(--p-blue-bg);color:var(--p-blue)}
.mcb-option.selected{background:var(--p-blue-bg)}
.mcb-option mark{background:none;color:var(--p-blue);font-weight:600}
.mcb-check{
    width:15px;height:15px;border-radius:4px;border:1.5px solid var(--p-border);
    flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all .1s;
}
.mcb-option.selected .mcb-check{background:var(--p-blue);border-color:var(--p-blue)}
.mcb-option.selected .mcb-check::after{content:'✓';color:#fff;font-size:10px;line-height:1}
.mcb-empty{padding:10px 12px;font-size:13px;color:var(--p-subtle);font-style:italic}

.new-row{display:grid;grid-template-columns:1fr 140px 1fr auto;gap:10px;align-items:end}
@media(max-width:640px){.new-row{grid-template-columns:1fr}}

/* Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;display:none;align-items:center;justify-content:center;padding:20px}
.modal-backdrop.open{display:flex}
.modal{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.modal-title{font-size:15px;font-weight:600}
.modal-close{background:none;border:none;cursor:pointer;color:var(--p-muted);font-size:20px;line-height:1}
.modal-close:hover{color:var(--p-text)}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.modal-foot{padding:16px 20px;border-top:1px solid var(--p-border);display:flex;gap:8px;justify-content:flex-end}
.field-label{font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.toggle-row{display:flex;align-items:center;gap:8px;font-size:13.5px}

/* ── Searchable Combobox ── */
.combobox{position:relative;width:100%}
.combobox-input-wrap{position:relative;display:flex;align-items:center}
.combobox-input{
    width:100%;padding:8px 32px 8px 12px;
    border:1px solid var(--p-border);border-radius:var(--p-rsm);
    background:var(--p-bg);color:var(--p-text);
    font-family:var(--p-font);font-size:13.5px;
    outline:none;transition:border-color .12s;cursor:pointer;
}
.combobox-input:focus{border-color:var(--p-blue)}
.combobox-chevron{
    position:absolute;right:10px;pointer-events:none;
    color:var(--p-muted);transition:transform .15s;
}
.combobox.open .combobox-chevron{transform:rotate(180deg)}
.combobox-clear{
    position:absolute;right:28px;background:none;border:none;
    color:var(--p-muted);cursor:pointer;font-size:14px;line-height:1;
    padding:0 2px;display:none;
}
.combobox-clear:hover{color:var(--p-text)}
.combobox-clear.visible{display:block}

.combobox-dropdown{
    position:absolute;top:calc(100% + 4px);left:0;right:0;
    background:var(--p-surface);border:1px solid var(--p-border);
    border-radius:var(--p-rsm);box-shadow:0 8px 24px rgba(0,0,0,.12);
    z-index:200;max-height:220px;overflow-y:auto;display:none;
}
.combobox.open .combobox-dropdown{display:block}

.combobox-option{
    padding:8px 12px;font-size:13.5px;cursor:pointer;
    color:var(--p-text);transition:background .1s;
    display:flex;align-items:center;gap:8px;
}
.combobox-option:hover,.combobox-option.focused{background:var(--p-blue-bg);color:var(--p-blue)}
.combobox-option.selected{font-weight:600}
.combobox-option mark{background:none;color:var(--p-blue);font-weight:600}
.combobox-empty{padding:10px 12px;font-size:13px;color:var(--p-subtle);font-style:italic}
.combobox-badge{font-size:10.5px;padding:1px 6px;border-radius:99px;background:var(--p-bg);border:1px solid var(--p-border);color:var(--p-muted);font-family:monospace;flex-shrink:0}
</style>

{{-- Abteilungen als JS-Array bereitstellen --}}
<script>
const ABTEILUNGEN = @json($abteilungen->map(fn($a) => ['id' => $a->id, 'name' => $a->name, 'kuerzel' => $a->kuerzel]));
const AUSBILDER   = @json($ausbilder->map(fn($a) => ['id' => $a->id, 'name' => $a->full_name]));
</script>

<div class="page">

    <div class="page-head">
        <div>
            <div class="page-title">Klassenverwaltung</div>
            <div class="page-sub">{{ $klassen->count() }} Klasse{{ $klassen->count() !== 1 ? 'n' : '' }}</div>
        </div>
    </div>

    @if(session('success'))<div class="flash ok">✓ {{ session('success') }}</div>@endif
    @if(session('error'))<div class="flash err">✕ {{ session('error') }}</div>@endif

    {{-- Neue Klasse --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Neue Klasse erstellen</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.klassen.erstellen') }}">
                @csrf
                <div class="new-row">
                    <input type="text" name="name" class="fi" placeholder="Klassenname (z.B. IT-2024)" required>
                    <input type="text" name="jahrgang" class="fi" placeholder="Jahrgang">
                    {{-- Searchable Abteilung --}}
                    <div>
                        <div class="field-label" style="margin-bottom:5px">Abteilung</div>
                        <div class="combobox" id="cb-new">
                            <input type="hidden" name="abteilung_id" id="cb-new-val">
                            <div class="combobox-input-wrap">
                                <input type="text" class="combobox-input" id="cb-new-input"
                                       placeholder="Abteilung suchen…" autocomplete="off" readonly>
                                <button type="button" class="combobox-clear" id="cb-new-clear" title="Auswahl entfernen">×</button>
                                <svg class="combobox-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            </div>
                            <div class="combobox-dropdown" id="cb-new-dd"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-blue" style="align-self:flex-end">+ Erstellen</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Klassen --}}
    @forelse($klassen as $klasse)
        <div class="klasse-card">
            <div class="klasse-head">
                <div>
                    <div class="klasse-name">
                        {{ $klasse->name }}
                        @if($klasse->aktiv)
                            <span class="pill pill-green" style="margin-left:8px;vertical-align:middle">Aktiv</span>
                        @else
                            <span class="pill pill-gray" style="margin-left:8px;vertical-align:middle">Inaktiv</span>
                        @endif
                    </div>
                    <div class="klasse-meta">
                        @if($klasse->jahrgang)<span>Jahrgang {{ $klasse->jahrgang }}</span>@endif
                        @if($klasse->abteilung)<span>📁 {{ $klasse->abteilung->name }}</span>@endif
                        <span>{{ $klasse->azubis->count() }} Azubi{{ $klasse->azubis->count() !== 1 ? 's' : '' }}</span>
                        <span>{{ $klasse->ausbilder->count() }} Ausbilder</span>
                    </div>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0">
                    <button class="action-btn action-edit"
                        onclick="openEdit(
                            {{ $klasse->id }},
                            '{{ addslashes($klasse->name) }}',
                            '{{ $klasse->jahrgang }}',
                            {{ $klasse->aktiv ? 'true' : 'false' }},
                            {{ $klasse->abteilung_id ?? 'null' }},
                            '{{ addslashes($klasse->abteilung?->name ?? '') }}'
                        )">
                        ✏ Bearbeiten
                    </button>
                    <form method="POST" action="{{ route('admin.klassen.loeschen', $klasse->id) }}" onsubmit="return confirm('Klasse wirklich löschen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="action-btn action-del">🗑</button>
                    </form>
                </div>
            </div>
            <div class="klasse-body">

                {{-- Ausbilder zuweisen --}}
                <form method="POST" action="{{ route('admin.klassen.ausbilder', $klasse->id) }}" id="ausbilder-form-{{ $klasse->id }}">
                    @csrf @method('PATCH')
                    <div class="section-label">Ausbilder</div>
                    @if($ausbilder->isEmpty())
                        <span style="font-size:13px;color:var(--p-subtle)">Keine Ausbilder vorhanden.</span>
                    @else
                        <div id="mcb-{{ $klasse->id }}"
                             class="mcb-wrap"
                             data-selected="{{ json_encode($klasse->ausbilder->pluck('id')) }}"
                             style="margin-bottom:10px">
                            <div class="mcb-field" onclick="mcbFocus({{ $klasse->id }})">
                                <div id="mcb-tags-{{ $klasse->id }}" style="display:contents"></div>
                                <input class="mcb-input" id="mcb-input-{{ $klasse->id }}"
                                       placeholder="Ausbilder suchen…" autocomplete="off">
                            </div>
                            <svg class="mcb-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            <div class="mcb-dropdown" id="mcb-dd-{{ $klasse->id }}"></div>
                            <div id="mcb-hidden-{{ $klasse->id }}"></div>
                        </div>
                        <button type="submit" class="btn btn-gray btn-sm">Speichern</button>
                    @endif
                </form>

                {{-- Azubis --}}
                @if($klasse->azubis->count())
                    <div>
                        <div class="section-label">Azubis</div>
                        <div class="tag-list">
                            @foreach($klasse->azubis as $azubi)
                                <span class="tag">{{ $azubi->full_name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body" style="text-align:center;color:var(--p-muted);font-size:13.5px;padding:32px">
                Noch keine Klassen vorhanden.
            </div>
        </div>
    @endforelse

</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="modal-backdrop" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title">Klasse bearbeiten</span>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" id="edit-form">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div>
                    <div class="field-label">Name</div>
                    <input type="text" name="name" id="edit-name" class="fi" required>
                </div>
                <div>
                    <div class="field-label">Jahrgang</div>
                    <input type="text" name="jahrgang" id="edit-jahrgang" class="fi" placeholder="z.B. 2024">
                </div>
                <div>
                    <div class="field-label">Abteilung</div>
                    <div class="combobox" id="cb-edit">
                        <input type="hidden" name="abteilung_id" id="cb-edit-val">
                        <div class="combobox-input-wrap">
                            <input type="text" class="combobox-input" id="cb-edit-input"
                                   placeholder="Abteilung suchen…" autocomplete="off" readonly>
                            <button type="button" class="combobox-clear" id="cb-edit-clear" title="Auswahl entfernen">×</button>
                            <svg class="combobox-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="combobox-dropdown" id="cb-edit-dd"></div>
                    </div>
                </div>
                <label class="toggle-row">
                    <input type="checkbox" name="aktiv" id="edit-aktiv" value="1" style="width:15px;height:15px;accent-color:var(--p-blue)">
                    <span style="font-size:13.5px">Klasse aktiv</span>
                </label>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-gray" onclick="closeModal()">Abbrechen</button>
                <button type="submit" class="btn btn-blue">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Combobox Engine ──────────────────────────────────────────────────────────
function createCombobox(cfg) {
    // cfg: { wrapperId, inputId, dropdownId, hiddenId, clearId }
    const wrap  = document.getElementById(cfg.wrapperId);
    const input = document.getElementById(cfg.inputId);
    const dd    = document.getElementById(cfg.dropdownId);
    const hidden= document.getElementById(cfg.hiddenId);
    const clear = document.getElementById(cfg.clearId);

    let focusedIndex = -1;
    let selectedId   = null;

    function highlight(text, query) {
        if (!query) return text;
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx === -1) return text;
        return text.slice(0, idx) + '<mark>' + text.slice(idx, idx + query.length) + '</mark>' + text.slice(idx + query.length);
    }

    function renderOptions(query) {
        const filtered = ABTEILUNGEN.filter(a =>
            a.name.toLowerCase().includes(query.toLowerCase()) ||
            (a.kuerzel && a.kuerzel.toLowerCase().includes(query.toLowerCase()))
        );

        dd.innerHTML = '';
        focusedIndex = -1;

        if (filtered.length === 0) {
            dd.innerHTML = '<div class="combobox-empty">Keine Abteilungen gefunden</div>';
            return;
        }

        filtered.forEach((a, i) => {
            const opt = document.createElement('div');
            opt.className = 'combobox-option' + (a.id === selectedId ? ' selected' : '');
            opt.dataset.id   = a.id;
            opt.dataset.name = a.name;
            opt.dataset.idx  = i;
            opt.innerHTML =
                '<span>' + highlight(a.name, query) + '</span>' +
                (a.kuerzel ? '<span class="combobox-badge">' + a.kuerzel + '</span>' : '');
            opt.addEventListener('mousedown', e => {
                e.preventDefault();
                selectOption(a.id, a.name);
            });
            dd.appendChild(opt);
        });
    }

    function selectOption(id, name) {
        selectedId   = id;
        hidden.value = id ?? '';
        input.value  = name ?? '';
        input.readOnly = true;
        clear.classList.toggle('visible', !!id);
        closeDropdown();
    }

    function openDropdown() {
        input.readOnly = false;
        input.value = '';
        renderOptions('');
        wrap.classList.add('open');
        input.focus();
    }

    function closeDropdown() {
        wrap.classList.remove('open');
        // Restore display name if something is selected
        if (selectedId) {
            const a = ABTEILUNGEN.find(x => x.id === selectedId);
            if (a) input.value = a.name;
        }
        input.readOnly = true;
    }

    // Click on input → open
    input.addEventListener('click', () => {
        if (wrap.classList.contains('open')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    // Typing → filter
    input.addEventListener('input', () => {
        renderOptions(input.value);
        wrap.classList.add('open');
    });

    // Keyboard navigation
    input.addEventListener('keydown', e => {
        const opts = dd.querySelectorAll('.combobox-option');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            focusedIndex = Math.min(focusedIndex + 1, opts.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            focusedIndex = Math.max(focusedIndex - 1, 0);
        } else if (e.key === 'Enter' && focusedIndex >= 0) {
            e.preventDefault();
            const opt = opts[focusedIndex];
            if (opt) selectOption(parseInt(opt.dataset.id), opt.dataset.name);
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
        opts.forEach((o, i) => o.classList.toggle('focused', i === focusedIndex));
        if (focusedIndex >= 0 && opts[focusedIndex]) {
            opts[focusedIndex].scrollIntoView({ block: 'nearest' });
        }
    });

    // Clear button
    clear.addEventListener('click', () => {
        selectOption(null, '');
        input.value = '';
        clear.classList.remove('visible');
    });

    // Close on outside click
    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) closeDropdown();
    });

    // Public API
    return {
        setValue(id, name) {
            selectOption(id, name);
        },
        reset() {
            selectOption(null, '');
            input.value = '';
        }
    };
}

// ── Multi-Select Combobox (Ausbilder) ────────────────────────────────────────
function createMultiCombobox(klasseId) {
    const wrap    = document.getElementById('mcb-' + klasseId);
    const input   = document.getElementById('mcb-input-' + klasseId);
    const dd      = document.getElementById('mcb-dd-' + klasseId);
    const tagsEl  = document.getElementById('mcb-tags-' + klasseId);
    const hiddenEl= document.getElementById('mcb-hidden-' + klasseId);

    let selected = new Set(JSON.parse(wrap.dataset.selected || '[]').map(Number));
    let focusedIndex = -1;

    function highlight(text, query) {
        if (!query) return text;
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx === -1) return text;
        return text.slice(0, idx) + '<mark>' + text.slice(idx, idx + query.length) + '</mark>' + text.slice(idx + query.length);
    }

    function renderTags() {
        tagsEl.innerHTML = '';
        hiddenEl.innerHTML = '';
        selected.forEach(id => {
            const a = AUSBILDER.find(x => x.id === id);
            if (!a) return;
            // Tag
            const tag = document.createElement('span');
            tag.className = 'mcb-tag';
            tag.innerHTML = a.name + '<button type="button" class="mcb-tag-remove" data-id="' + id + '">×</button>';
            tag.querySelector('.mcb-tag-remove').addEventListener('click', e => {
                e.stopPropagation();
                selected.delete(id);
                renderTags();
                renderOptions(input.value);
            });
            tagsEl.appendChild(tag);
            // Hidden input
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'ausbilder_ids[]';
            inp.value = id;
            hiddenEl.appendChild(inp);
        });
    }

    function renderOptions(query) {
        const filtered = AUSBILDER.filter(a =>
            a.name.toLowerCase().includes(query.toLowerCase())
        );
        dd.innerHTML = '';
        focusedIndex = -1;
        if (filtered.length === 0) {
            dd.innerHTML = '<div class="mcb-empty">Keine Ausbilder gefunden</div>';
            return;
        }
        filtered.forEach((a, i) => {
            const isSel = selected.has(a.id);
            const opt = document.createElement('div');
            opt.className = 'mcb-option' + (isSel ? ' selected' : '');
            opt.dataset.id  = a.id;
            opt.dataset.idx = i;
            opt.innerHTML = '<div class="mcb-check"></div><span>' + highlight(a.name, query) + '</span>';
            opt.addEventListener('mousedown', e => {
                e.preventDefault();
                if (selected.has(a.id)) selected.delete(a.id);
                else selected.add(a.id);
                renderTags();
                renderOptions(input.value);
                input.focus();
            });
            dd.appendChild(opt);
        });
    }

    function openDropdown() {
        renderOptions(input.value);
        wrap.classList.add('open');
    }
    function closeDropdown() {
        wrap.classList.remove('open');
        input.value = '';
        focusedIndex = -1;
    }

    input.addEventListener('focus', openDropdown);
    input.addEventListener('input', () => { renderOptions(input.value); wrap.classList.add('open'); });
    input.addEventListener('keydown', e => {
        const opts = dd.querySelectorAll('.mcb-option');
        if (e.key === 'ArrowDown') { e.preventDefault(); focusedIndex = Math.min(focusedIndex + 1, opts.length - 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); focusedIndex = Math.max(focusedIndex - 1, 0); }
        else if (e.key === 'Enter' && focusedIndex >= 0) {
            e.preventDefault();
            const opt = opts[focusedIndex];
            if (opt) {
                const id = parseInt(opt.dataset.id);
                if (selected.has(id)) selected.delete(id); else selected.add(id);
                renderTags(); renderOptions(input.value);
            }
        } else if (e.key === 'Escape') { closeDropdown(); input.blur(); }
        opts.forEach((o, i) => o.classList.toggle('focused', i === focusedIndex));
        if (focusedIndex >= 0 && opts[focusedIndex]) opts[focusedIndex].scrollIntoView({ block: 'nearest' });
    });

    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) closeDropdown();
    });

    renderTags();
}

window.mcbFocus = function(id) {
    document.getElementById('mcb-input-' + id).focus();
};

// ── Init Comboboxes ──────────────────────────────────────────────────────────
let cbNew, cbEdit;
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="mcb-"][data-selected]').forEach(el => {
        const id = el.id.replace('mcb-', '');
        createMultiCombobox(id);
    });

    cbNew = createCombobox({
        wrapperId: 'cb-new',
        inputId:   'cb-new-input',
        dropdownId:'cb-new-dd',
        hiddenId:  'cb-new-val',
        clearId:   'cb-new-clear',
    });
    cbEdit = createCombobox({
        wrapperId: 'cb-edit',
        inputId:   'cb-edit-input',
        dropdownId:'cb-edit-dd',
        hiddenId:  'cb-edit-val',
        clearId:   'cb-edit-clear',
    });
});

// ── Modal ────────────────────────────────────────────────────────────────────
function openEdit(id, name, jahrgang, aktiv, abteilungId, abteilungName) {
    document.getElementById('edit-form').action = `/admin/klassen/${id}`;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-jahrgang').value = jahrgang || '';
    document.getElementById('edit-aktiv').checked = aktiv;
    if (abteilungId) {
        cbEdit.setValue(abteilungId, abteilungName);
    } else {
        cbEdit.reset();
    }
    document.getElementById('edit-modal').classList.add('open');
}

function closeModal() {
    document.getElementById('edit-modal').classList.remove('open');
}
</script>
</x-app-layout>
