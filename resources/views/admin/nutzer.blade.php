<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
:root {
    --p-bg:#F4F7FB;--p-surface:#fff;--p-border:#E2E8F0;--p-text:#1A2233;--p-muted:#64748B;--p-subtle:#94A3B8;
    --p-blue:#2563EB;--p-blue-bg:#EEF3FF;--p-green:#16A34A;--p-green-bg:#DCFCE7;
    --p-amber:#D97706;--p-amber-bg:#FEF3C7;--p-red:#DC2626;--p-red-bg:#FEE2E2;
    --p-purple:#7C3AED;--p-purple-bg:#F3EEFF;
    --p-radius:12px;--p-rsm:8px;--p-font:'DM Sans',system-ui,sans-serif;
}
html[data-theme="dark"] {
    --p-bg:#0D1117;--p-surface:#161B22;--p-border:#30363D;--p-text:#E6EDF3;--p-muted:#8B949E;--p-subtle:#484F58;
    --p-blue:#58A6FF;--p-blue-bg:#0D2340;--p-green:#3FB950;--p-green-bg:#0A2918;
    --p-amber:#E3B341;--p-amber-bg:#2D1F00;--p-red:#F85149;--p-red-bg:#2D0A0A;
    --p-purple:#A78BFA;--p-purple-bg:#1E1040;
}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}
.page{max-width:1100px;margin:0 auto;padding:28px 20px 48px;display:flex;flex-direction:column;gap:20px}
.page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.page-title{font-size:21px;font-weight:700;letter-spacing:-.4px}
.page-sub{font-size:13px;color:var(--p-muted);margin-top:3px}

.flash{padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid}
.flash.ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash.err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius)}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border);border-radius:var(--p-radius) var(--p-radius) 0 0}
.card-title{font-size:14px;font-weight:600;color:var(--p-text)}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--p-rsm);font-size:13.5px;font-weight:500;font-family:var(--p-font);cursor:pointer;border:none;transition:opacity .15s;text-decoration:none}
.btn:hover{opacity:.85}
.btn-blue{background:var(--p-blue);color:#fff}
.btn-gray{background:var(--p-border);color:var(--p-text)}
.btn-sm{padding:5px 12px;font-size:12.5px}

.dan-table{width:100%;border-collapse:collapse}
.dan-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--p-border);background:var(--p-bg)}
.dan-table td{padding:11px 16px;font-size:13.5px;border-bottom:1px solid var(--p-border);vertical-align:middle}
.dan-table tr:last-child td{border-bottom:none}
.dan-table tbody tr:hover{background:var(--p-bg)}

.name-main{font-weight:500}
.name-sub{font-size:12px;color:var(--p-subtle);margin-top:1px}

.pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}
.pill-red{background:var(--p-red-bg);color:var(--p-red)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}
.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}

.action-btn{font-size:12.5px;font-weight:500;padding:5px 10px;border-radius:var(--p-rsm);border:none;cursor:pointer;font-family:var(--p-font);transition:opacity .15s;display:inline-flex;align-items:center;gap:4px}
.action-btn:hover{opacity:.8}
.action-edit{background:var(--p-border);color:var(--p-text)}
.action-del{background:var(--p-red-bg);color:var(--p-red)}
.action-archive{background:var(--p-amber-bg);color:var(--p-amber)}
.action-restore{background:var(--p-green-bg);color:var(--p-green)}

/* Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;display:none;align-items:center;justify-content:center;padding:20px}
.modal-backdrop.open{display:flex}
.modal{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.modal-title{font-size:15px;font-weight:600}
.modal-close{background:none;border:none;cursor:pointer;color:var(--p-muted);font-size:18px;padding:0;line-height:1}
.modal-close:hover{color:var(--p-text)}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.modal-foot{padding:16px 20px;border-top:1px solid var(--p-border);display:flex;gap:8px;justify-content:flex-end}
.field-label{font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%}
.fi:focus{border-color:var(--p-blue)}
.toggle-row{display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--p-text)}

/* ── Filterleiste ── */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.search-wrap{position:relative;flex:1;min-width:180px}
.search-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--p-muted);pointer-events:none}
.search-input{width:100%;padding:8px 34px 8px 34px;border:1px solid var(--p-border);border-radius:var(--p-rsm);background:var(--p-bg);color:var(--p-text);font-family:var(--p-font);font-size:13.5px;outline:none;transition:border-color .12s}
.search-input:focus{border-color:var(--p-blue)}
.search-input::placeholder{color:var(--p-subtle)}
.search-clear{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--p-muted);font-size:16px;line-height:1;padding:0;display:none}
.search-clear.visible{display:block}

/* Rolle-Chips */
.role-filters{display:flex;gap:5px;flex-wrap:wrap}
.role-chip{padding:6px 12px;border-radius:99px;font-size:12.5px;font-weight:500;cursor:pointer;border:1.5px solid var(--p-border);background:var(--p-surface);color:var(--p-muted);font-family:var(--p-font);transition:all .12s;white-space:nowrap}
.role-chip:hover{border-color:var(--p-blue);color:var(--p-blue)}
.role-chip.active-all{background:var(--p-text);border-color:var(--p-text);color:var(--p-surface)}
.role-chip.active-azubi{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.role-chip.active-ausbilder{background:var(--p-blue-bg);border-color:var(--p-blue);color:var(--p-blue)}
.role-chip.active-admin{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

/* Dropdown-Filter */
.filter-select-wrap{position:relative;min-width:150px}
.filter-select{width:100%;padding:7px 28px 7px 11px;border:1.5px solid var(--p-border);border-radius:var(--p-rsm);background:var(--p-surface);color:var(--p-text);font-family:var(--p-font);font-size:13px;outline:none;cursor:pointer;appearance:none;-webkit-appearance:none;transition:border-color .12s}
.filter-select:focus,.filter-select.active{border-color:var(--p-blue)}
.filter-chevron{position:absolute;right:9px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--p-muted)}
.filter-count{font-size:12.5px;color:var(--p-subtle);white-space:nowrap;margin-left:auto}

/* Archiv-Sektion */
.archiv-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:500;color:var(--p-muted);padding:14px 20px;border-top:1px solid var(--p-border);user-select:none;transition:color .12s}
.archiv-toggle:hover{color:var(--p-text)}
.archiv-chevron{transition:transform .2s}
.archiv-chevron.open{transform:rotate(180deg)}
.archiv-body{display:none}
.archiv-body.open{display:block}
.archiv-date{font-size:11.5px;color:var(--p-subtle)}
</style>

{{-- Daten als JS-Arrays --}}
<script>
const KLASSEN_DATA    = @json($klassen->map(fn($k) => ['id' => $k->id, 'name' => $k->name, 'abteilung_id' => $k->abteilung_id]));
const ABTEILUNGEN_DATA= @json($abteilungen->map(fn($a) => ['id' => $a->id, 'name' => $a->name]));
</script>

<div class="page">
    <div class="page-head">
        <div>
            <div class="page-title">Nutzerverwaltung</div>
            <div class="page-sub">{{ $nutzer->count() }} aktive Nutzer · {{ $archivierte->count() }} archiviert</div>
        </div>
        <a href="{{ route('admin.einladungen') }}" class="btn btn-blue">✉ Einladung senden</a>
    </div>

    @if(session('success'))<div class="flash ok">✓ {{ session('success') }}</div>@endif
    @if(session('error'))<div class="flash err">✕ {{ session('error') }}</div>@endif

    {{-- Aktive Nutzer --}}
    <div class="card">
        <div class="card-header" style="flex-direction:column;align-items:stretch;gap:14px;padding-bottom:14px">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span class="card-title">Aktive Nutzer</span>
                <span class="filter-count" id="filter-count"></span>
            </div>
            <div class="filter-bar">
                {{-- Freitextsuche --}}
                <div class="search-wrap">
                    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" class="search-input" id="nutzer-search" placeholder="Name oder E-Mail…" oninput="filterNutzer()">
                    <button class="search-clear" id="search-clear" onclick="clearSearch()">×</button>
                </div>
                {{-- Rolle --}}
                <div class="role-filters">
                    <button class="role-chip active-all" data-role="all" onclick="setRoleFilter('all',this)">Alle</button>
                    <button class="role-chip" data-role="azubi" onclick="setRoleFilter('azubi',this)">Azubi</button>
                    <button class="role-chip" data-role="ausbilder" onclick="setRoleFilter('ausbilder',this)">Ausbilder</button>
                    <button class="role-chip" data-role="admin" onclick="setRoleFilter('admin',this)">Admin</button>
                </div>
                {{-- Abteilung --}}
                <div class="filter-select-wrap">
                    <select class="filter-select" id="filter-abteilung" onchange="filterNutzer()">
                        <option value="">Alle Abteilungen</option>
                        @foreach($abteilungen as $abt)
                            <option value="{{ $abt->id }}">{{ $abt->name }}</option>
                        @endforeach
                    </select>
                    <svg class="filter-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                {{-- Klasse --}}
                <div class="filter-select-wrap">
                    <select class="filter-select" id="filter-klasse" onchange="filterNutzer()">
                        <option value="">Alle Klassen</option>
                        @foreach($klassen as $kl)
                            <option value="{{ $kl->id }}">{{ $kl->name }}</option>
                        @endforeach
                    </select>
                    <svg class="filter-chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
        </div>

        <table class="dan-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Klasse</th>
                    <th>Abteilung</th>
                    <th>Status</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody id="nutzer-tbody">
                @foreach($nutzer as $user)
                    <tr data-name="{{ strtolower($user->full_name) }}"
                        data-email="{{ strtolower($user->email) }}"
                        data-rolle="{{ $user->rolle }}"
                        data-klasse-id="{{ $user->klasse_id ?? '' }}"
                        data-abteilung-id="{{ $user->klasse?->abteilung_id ?? '' }}">
                        <td><div class="name-main">{{ $user->full_name }}</div></td>
                        <td style="color:var(--p-muted);font-size:13px">{{ $user->email }}</td>
                        <td>
                            @if($user->rolle === 'admin')     <span class="pill pill-red">Admin</span>
                            @elseif($user->rolle === 'ausbilder') <span class="pill pill-blue">Ausbilder</span>
                            @else <span class="pill pill-green">Azubi</span>
                            @endif
                        </td>
                        <td style="color:var(--p-muted);font-size:13px">{{ $user->klasse?->name ?? '—' }}</td>
                        <td style="color:var(--p-muted);font-size:13px">{{ $user->klasse?->abteilung?->name ?? '—' }}</td>
                        <td>
                            @if($user->aktiv) <span class="pill pill-green">Aktiv</span>
                            @else <span class="pill pill-gray">Deaktiviert</span>
                            @endif
                        </td>
                        <td>
                            @if($user->id !== auth()->id())
                                <div style="display:flex;gap:5px;flex-wrap:wrap">
                                    <button class="action-btn action-edit"
                                        onclick="openEdit({{ $user->id }},'{{ $user->rolle }}',{{ $user->aktiv ? 'true' : 'false' }},{{ $user->klasse_id ?? 'null' }})">
                                        ✏
                                    </button>
                                    <form method="POST" action="{{ route('admin.nutzer.archivieren', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="action-btn action-archive" title="Archivieren"
                                                onclick="return confirm('{{ $user->full_name }} archivieren?')">📦</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.nutzer.loeschen', $user->id) }}"
                                          onsubmit="return confirm('Nutzer wirklich löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn action-del">🗑</button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size:12px;color:var(--p-subtle)">— Du —</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div id="no-results" style="display:none;text-align:center;padding:32px;color:var(--p-subtle);font-size:13.5px">
            Keine Nutzer gefunden.
        </div>
    </div>

    {{-- Archivierte Nutzer --}}
    @if($archivierte->count() > 0)
    <div class="card">
        <div class="archiv-toggle" onclick="toggleArchiv(this)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
            Archivierte Nutzer
            <span class="pill pill-gray" style="margin-left:4px">{{ $archivierte->count() }}</span>
            <svg class="archiv-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:auto"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="archiv-body" id="archiv-body">
            <table class="dan-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Klasse</th>
                        <th>Archiviert am</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($archivierte as $user)
                        <tr style="opacity:.75">
                            <td><div class="name-main">{{ $user->full_name }}</div></td>
                            <td style="color:var(--p-muted);font-size:13px">{{ $user->email }}</td>
                            <td>
                                @if($user->rolle === 'admin')     <span class="pill pill-red">Admin</span>
                                @elseif($user->rolle === 'ausbilder') <span class="pill pill-blue">Ausbilder</span>
                                @else <span class="pill pill-green">Azubi</span>
                                @endif
                            </td>
                            <td style="color:var(--p-muted);font-size:13px">{{ $user->klasse?->name ?? '—' }}</td>
                            <td class="archiv-date">{{ $user->archiviert_am?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                <div style="display:flex;gap:5px">
                                    <form method="POST" action="{{ route('admin.nutzer.wiederherstellen', $user->id) }}">
                                        @csrf
                                        <button type="submit" class="action-btn action-restore" title="Wiederherstellen">↩ Wiederherstellen</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.nutzer.loeschen', $user->id) }}"
                                          onsubmit="return confirm('Nutzer endgültig löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn action-del">🗑</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="modal-backdrop" onclick="if(event.target===this)closeModal()">
    <div class="modal">
        <div class="modal-head">
            <span class="modal-title">Nutzer bearbeiten</span>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <form method="POST" id="edit-form">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div>
                    <div class="field-label">Rolle</div>
                    <select name="rolle" id="edit-rolle" class="fi" onchange="toggleKlasse()">
                        <option value="azubi">Azubi</option>
                        <option value="ausbilder">Ausbilder</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div id="klasse-field">
                    <div class="field-label">Klasse</div>
                    <select name="klasse_id" id="edit-klasse" class="fi">
                        <option value="">— Keine —</option>
                        @foreach($klassen as $klasse)
                            <option value="{{ $klasse->id }}">{{ $klasse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="toggle-row">
                    <input type="checkbox" name="aktiv" id="edit-aktiv" value="1">
                    Nutzer aktiv
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
// ── Filter ───────────────────────────────────────────────────────────────────
let activeRole = 'all';

function filterNutzer() {
    const query    = document.getElementById('nutzer-search').value.toLowerCase();
    const abtId    = document.getElementById('filter-abteilung').value;
    const klasseId = document.getElementById('filter-klasse').value;
    const rows     = document.querySelectorAll('#nutzer-tbody tr');
    const clear    = document.getElementById('search-clear');
    let visible    = 0;

    clear.classList.toggle('visible', query.length > 0);

    rows.forEach(row => {
        const matchSearch  = !query     || row.dataset.name.includes(query) || row.dataset.email.includes(query);
        const matchRole    = activeRole === 'all' || row.dataset.rolle === activeRole;
        const matchAbt     = !abtId     || row.dataset.abteilungId === abtId;
        const matchKlasse  = !klasseId  || row.dataset.klasseId === klasseId;

        const show = matchSearch && matchRole && matchAbt && matchKlasse;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('no-results').style.display = visible === 0 ? 'block' : 'none';
    document.getElementById('filter-count').textContent = visible + ' von ' + rows.length;
}

function setRoleFilter(role, btn) {
    activeRole = role;
    // Wenn Azubi gewählt → Klassen-Filter nur Azubi-Klassen zeigen (optional, hier einfach alle)
    document.querySelectorAll('.role-chip').forEach(c => {
        c.className = 'role-chip';
        if (c.dataset.role === role) c.classList.add('active-' + role);
    });
    filterNutzer();
}

function clearSearch() {
    document.getElementById('nutzer-search').value = '';
    filterNutzer();
}

// ── Archiv-Toggle ────────────────────────────────────────────────────────────
function toggleArchiv(btn) {
    const body    = document.getElementById('archiv-body');
    const chevron = btn.querySelector('.archiv-chevron');
    body.classList.toggle('open');
    chevron.classList.toggle('open');
}

// ── Modal ────────────────────────────────────────────────────────────────────
function openEdit(id, rolle, aktiv, klasseId) {
    document.getElementById('edit-form').action = `/admin/nutzer/${id}`;
    document.getElementById('edit-rolle').value = rolle;
    document.getElementById('edit-aktiv').checked = aktiv;
    document.getElementById('edit-klasse').value = klasseId || '';
    toggleKlasse();
    document.getElementById('edit-modal').classList.add('open');
}
function closeModal() {
    document.getElementById('edit-modal').classList.remove('open');
}
function toggleKlasse() {
    const rolle = document.getElementById('edit-rolle').value;
    document.getElementById('klasse-field').style.display = rolle === 'azubi' ? 'block' : 'none';
}

// Init count
document.addEventListener('DOMContentLoaded', () => {
    const total = document.querySelectorAll('#nutzer-tbody tr').length;
    document.getElementById('filter-count').textContent = total + ' von ' + total;
});
</script>
</x-app-layout>
