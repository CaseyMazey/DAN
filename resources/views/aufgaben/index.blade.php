<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
:root {
    --p-bg:#F4F7FB;--p-surface:#fff;--p-border:#E2E8F0;
    --p-text:#1A2233;--p-muted:#64748B;--p-subtle:#94A3B8;
    --p-blue:#2563EB;--p-blue-bg:#EEF3FF;--p-blue-light:#DBEAFE;
    --p-green:#16A34A;--p-green-bg:#DCFCE7;
    --p-amber:#D97706;--p-amber-bg:#FEF3C7;
    --p-red:#DC2626;--p-red-bg:#FEE2E2;
    --p-purple:#7C3AED;--p-purple-bg:#F3F0FF;
    --p-gray-bg:#F1F5F9;
    --p-radius:12px;--p-rsm:8px;
    --p-font:'DM Sans',system-ui,sans-serif;
}
html[data-theme="dark"] {
    --p-bg:#0D1117;--p-surface:#161B22;--p-border:#30363D;
    --p-text:#E6EDF3;--p-muted:#8B949E;--p-subtle:#484F58;
    --p-blue:#58A6FF;--p-blue-bg:#0D2340;--p-blue-light:#0D2340;
    --p-green:#3FB950;--p-green-bg:#0A2918;
    --p-amber:#E3B341;--p-amber-bg:#2D1F00;
    --p-red:#F85149;--p-red-bg:#2D0A0A;
    --p-purple:#A78BFA;--p-purple-bg:#1A0D40;
    --p-gray-bg:#21262D;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}

.af-wrap{max-width:1100px;margin:0 auto;padding:28px 20px 60px}
.af-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.af-title{font-size:22px;font-weight:700;letter-spacing:-.4px}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--p-rsm);font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:none;font-family:var(--p-font);transition:all .12s;line-height:1}
.btn-primary{background:var(--p-blue);color:#fff}.btn-primary:hover{opacity:.88}
.btn-ghost{background:var(--p-surface);border:1px solid var(--p-border);color:var(--p-muted)}.btn-ghost:hover{border-color:var(--p-blue);color:var(--p-blue)}
.btn-sm{padding:5px 10px;font-size:12px}

.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500;white-space:nowrap}
.pill-blue  {background:var(--p-blue-bg);  color:var(--p-blue)}
.pill-green {background:var(--p-green-bg); color:var(--p-green)}
.pill-amber {background:var(--p-amber-bg); color:var(--p-amber)}
.pill-purple{background:var(--p-purple-bg);color:var(--p-purple)}
.pill-gray  {background:var(--p-gray-bg);  color:var(--p-muted)}

.flash{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid;margin-bottom:16px}
.flash-ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}

/* Hours card */
.hours-card{
    background:var(--p-surface);border:1px solid var(--p-border);
    border-radius:var(--p-radius);padding:18px 24px;
    display:flex;align-items:center;gap:24px;flex-wrap:wrap;
    margin-bottom:20px
}
.hours-stat{text-align:center;min-width:90px}
.hours-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-subtle);margin-bottom:4px}
.hours-value{font-size:24px;font-weight:700;letter-spacing:-.4px}
.hours-value.over{color:var(--p-red)}
.h-divider{width:1px;height:40px;background:var(--p-border);flex-shrink:0}
.hours-progress{flex:1;min-width:200px}
.hours-bar-track{height:6px;background:var(--p-border);border-radius:99px;overflow:hidden;margin-top:8px}
.hours-bar-fill{height:100%;border-radius:99px;background:var(--p-blue);transition:width .4s}
.hours-bar-fill.over{background:var(--p-red)}

/* Kanban */
.kanban{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;align-items:start}
@media(max-width:700px){.kanban{grid-template-columns:1fr}}

.kanban-col{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.kanban-head{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid var(--p-border);font-size:13px;font-weight:600}
.k-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.k-dot.gray {background:var(--p-subtle)}
.k-dot.amber{background:var(--p-amber)}
.k-dot.green{background:var(--p-green)}
.k-count{font-size:11.5px;font-weight:600;padding:2px 8px;border-radius:99px;background:var(--p-gray-bg);color:var(--p-muted)}

.kanban-body{padding:10px;display:flex;flex-direction:column;gap:8px;min-height:80px}
.kanban-empty{font-size:12.5px;color:var(--p-subtle);text-align:center;padding:16px 0}

/* Archive */
.archive-wrap{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden;margin-top:20px}
.archive-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--p-border);font-size:14px;font-weight:600}
.archive-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 20px;border-bottom:1px solid var(--p-border)}
.archive-row:last-child{border-bottom:none}.archive-row:hover{background:var(--p-gray-bg)}
.archive-name{font-size:13px;font-weight:500}
.archive-meta{font-size:12px;color:var(--p-muted);margin-top:1px}

/* Modals */
.modal-bd{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);align-items:center;justify-content:center;z-index:600;padding:20px}
.modal-bd.open{display:flex}
.modal{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:mIn .18s ease}
@keyframes mIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--p-border)}
.modal-title{font-size:15px;font-weight:700}
.modal-x{background:none;border:none;cursor:pointer;color:var(--p-muted);padding:4px;border-radius:4px;line-height:1}.modal-x:hover{color:var(--p-text);background:var(--p-gray-bg)}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.modal-ft{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid var(--p-border);background:var(--p-gray-bg)}

.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:12.5px;font-weight:600;color:var(--p-muted)}
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%}
.fi:focus{border-color:var(--p-blue)}
textarea.fi{resize:vertical;min-height:72px}
</style>

<div class="af-wrap">

    <div class="af-topbar">
        <div class="af-title">Aufgaben-Kanban</div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-ghost" onclick="openModal('modal-config')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                Stunden konfigurieren
            </button>
            <button class="btn btn-primary" onclick="openModal('modal-erstellen')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Neue Aufgabe
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="flash flash-ok">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Stunden --}}
    <div class="hours-card">
        <div class="hours-stat">
            <div class="hours-label">Betrieb Soll</div>
            <div class="hours-value">{{ $config->betrieb_stunden }}<span style="font-size:13px;font-weight:400;color:var(--p-muted)"> Std</span></div>
        </div>
        <div class="h-divider"></div>
        <div class="hours-stat">
            <div class="hours-label">Berufsschule Soll</div>
            <div class="hours-value">{{ $config->berufsschule_stunden }}<span style="font-size:13px;font-weight:400;color:var(--p-muted)"> Std</span></div>
        </div>
        <div class="h-divider"></div>
        <div class="hours-progress">
            <div style="display:flex;align-items:baseline;justify-content:space-between">
                <div class="hours-label">Diese Woche</div>
                <div class="hours-value {{ $ueberschritten ? 'over' : '' }}" style="font-size:19px">
                    {{ $stundenDieseWoche }}<span style="font-size:12px;font-weight:400;color:var(--p-muted)"> / {{ $config->max_stunden }} Std</span>
                </div>
            </div>
            @php $pct = $config->max_stunden > 0 ? min(100, round($stundenDieseWoche / $config->max_stunden * 100)) : 0; @endphp
            <div class="hours-bar-track">
                <div class="hours-bar-fill {{ $ueberschritten ? 'over' : '' }}" style="width:{{ $pct }}%"></div>
            </div>
            @if($ueberschritten)
                <div style="font-size:12px;color:var(--p-red);margin-top:5px;font-weight:500">⚠ Maximale Stundenanzahl überschritten</div>
            @endif
        </div>
    </div>

    {{-- Kanban --}}
    <div class="kanban">
        @foreach(['geplant' => ['Geplant','gray'], 'in_bearbeitung' => ['In Bearbeitung','amber'], 'fertig' => ['Fertig','green']] as $status => [$label, $color])
            <div class="kanban-col">
                <div class="kanban-head">
                    <span style="display:flex;align-items:center;gap:8px">
                        <span class="k-dot {{ $color }}"></span>{{ $label }}
                    </span>
                    <span class="k-count">{{ $aufgaben->get($status, collect())->count() }}</span>
                </div>
                <div class="kanban-body">
                    @forelse($aufgaben->get($status, collect()) as $aufgabe)
                        @include('aufgaben.partials.karte', ['aufgabe' => $aufgabe])
                    @empty
                        <div class="kanban-empty">Keine Aufgaben</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Archiv --}}
    @if($archiviert->count())
        <div class="archive-wrap">
            <div class="archive-head">
                <span style="display:flex;align-items:center;gap:8px">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    Archiv
                </span>
                <span class="pill pill-gray">max. 20</span>
            </div>
            @foreach($archiviert as $aufgabe)
                <div class="archive-row">
                    <div>
                        <div class="archive-name">{{ $aufgabe->titel }}</div>
                        <div class="archive-meta">
                            {{ ucfirst($aufgabe->kategorie) }} · {{ $aufgabe->stunden }} Std
                            @if($aufgabe->in_wochenbericht)
                                · <span style="color:var(--p-green);font-weight:500">Im Bericht</span>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('aufgaben.wiederherstellen', $aufgabe->id) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-ghost btn-sm">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                            Wiederherstellen
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Modal: Neue Aufgabe --}}
<div id="modal-erstellen" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Neue Aufgabe</span>
            <button class="modal-x" onclick="closeModal('modal-erstellen')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form method="POST" action="{{ route('aufgaben.store') }}">
            @csrf
            <div class="modal-body">
                <div class="fg"><label class="fl">Titel</label><input type="text" name="titel" class="fi" required placeholder="Aufgabenbezeichnung"></div>
                <div class="fg"><label class="fl">Beschreibung <span style="font-weight:400;color:var(--p-subtle)">(optional)</span></label><textarea name="beschreibung" class="fi" placeholder="Details, Kontext…"></textarea></div>
                <div class="fg">
                    <label class="fl">Kategorie</label>
                    <select name="kategorie" id="neu-kat" class="fi"
                        onchange="document.getElementById('neu-uw').style.display=this.value==='unterweisung'?'flex':'none'">
                        <option value="betrieb">Betrieb</option>
                        <option value="berufsschule">Berufsschule</option>
                        <option value="unterweisung">Unterweisung / Schulung</option>
                    </select>
                </div>
                <div id="neu-uw" style="display:none" class="fg">
                    <label class="fl">Stunden abziehen bei</label>
                    <select name="stunden_abziehen_bei" class="fi"><option value="betrieb">Betrieb</option><option value="berufsschule">Berufsschule</option></select>
                </div>
                <div class="fg"><label class="fl">Stunden</label><input type="number" name="stunden" step="0.5" min="0.5" max="99" class="fi" required placeholder="0.0"></div>
            </div>
            <div class="modal-ft">
                <button type="button" onclick="closeModal('modal-erstellen')" class="btn btn-ghost">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Stunden Config --}}
<div id="modal-config" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Stunden konfigurieren</span>
            <button class="modal-x" onclick="closeModal('modal-config')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form method="POST" action="{{ route('aufgaben.config') }}">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div class="fg"><label class="fl">Max. Stunden pro Woche</label><input type="number" name="max_stunden" step="0.5" value="{{ $config->max_stunden }}" class="fi" required></div>
                <div class="fg"><label class="fl">Betrieb Stunden (Soll)</label><input type="number" name="betrieb_stunden" step="0.5" value="{{ $config->betrieb_stunden }}" class="fi" required></div>
                <div class="fg"><label class="fl">Berufsschule Stunden (Soll)</label><input type="number" name="berufsschule_stunden" step="0.5" value="{{ $config->berufsschule_stunden }}" class="fi" required></div>
            </div>
            <div class="modal-ft">
                <button type="button" onclick="closeModal('modal-config')" class="btn btn-ghost">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Bearbeiten --}}
<div id="modal-bearbeiten" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Aufgabe bearbeiten</span>
            <button class="modal-x" onclick="closeModal('modal-bearbeiten')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form method="POST" id="form-bearbeiten">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div class="fg"><label class="fl">Titel</label><input type="text" name="titel" id="edit-titel" class="fi" required></div>
                <div class="fg"><label class="fl">Beschreibung</label><textarea name="beschreibung" id="edit-beschreibung" class="fi"></textarea></div>
                <div class="fg">
                    <label class="fl">Kategorie</label>
                    <select name="kategorie" id="edit-kat" class="fi"
                        onchange="document.getElementById('edit-uw').style.display=this.value==='unterweisung'?'flex':'none'">
                        <option value="betrieb">Betrieb</option>
                        <option value="berufsschule">Berufsschule</option>
                        <option value="unterweisung">Unterweisung / Schulung</option>
                    </select>
                </div>
                <div id="edit-uw" style="display:none" class="fg">
                    <label class="fl">Stunden abziehen bei</label>
                    <select name="stunden_abziehen_bei" id="edit-abziehen" class="fi"><option value="betrieb">Betrieb</option><option value="berufsschule">Berufsschule</option></select>
                </div>
                <div class="fg"><label class="fl">Stunden</label><input type="number" name="stunden" id="edit-stunden" step="0.5" min="0.5" max="99" class="fi" required></div>
            </div>
            <div class="modal-ft">
                <button type="button" onclick="closeModal('modal-bearbeiten')" class="btn btn-ghost">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open') }
function closeModal(id) { document.getElementById(id).classList.remove('open') }
document.querySelectorAll('.modal-bd').forEach(el =>
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open') })
)
function bearbeitenModal(id, titel, beschreibung, kategorie, stunden, stundenAbziehen) {
    document.getElementById('edit-titel').value = titel
    document.getElementById('edit-beschreibung').value = beschreibung
    document.getElementById('edit-kat').value = kategorie
    document.getElementById('edit-stunden').value = stunden
    document.getElementById('form-bearbeiten').action = '/aufgaben/' + id
    const uw = document.getElementById('edit-uw')
    uw.style.display = kategorie === 'unterweisung' ? 'flex' : 'none'
    if (kategorie === 'unterweisung') document.getElementById('edit-abziehen').value = stundenAbziehen
    openModal('modal-bearbeiten')
}
</script>
</x-app-layout>
