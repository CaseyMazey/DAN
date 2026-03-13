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
    --p-teal:#0D9488;--p-teal-bg:#F0FDFA;
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
    --p-teal:#2DD4BF;--p-teal-bg:#012D2A;
    --p-gray-bg:#21262D;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}

.kal-wrap{max-width:1100px;margin:0 auto;padding:28px 20px 60px}
.kal-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.kal-title{font-size:22px;font-weight:700;letter-spacing:-.4px}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--p-rsm);font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:none;font-family:var(--p-font);transition:all .12s;line-height:1}
.btn-primary{background:var(--p-blue);color:#fff}.btn-primary:hover{opacity:.88}
.btn-ghost{background:var(--p-surface);border:1px solid var(--p-border);color:var(--p-muted)}.btn-ghost:hover{border-color:var(--p-blue);color:var(--p-blue)}
.btn-sm{padding:5px 10px;font-size:12px}

.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500;white-space:nowrap}
.pill-blue  {background:var(--p-blue-bg);  color:var(--p-blue)}
.pill-green {background:var(--p-green-bg); color:var(--p-green)}
.pill-amber {background:var(--p-amber-bg); color:var(--p-amber)}
.pill-gray  {background:var(--p-gray-bg);  color:var(--p-muted)}
.pill-red   {background:var(--p-red-bg);   color:var(--p-red)}
.pill-purple{background:var(--p-purple-bg);color:var(--p-purple)}
.pill-teal  {background:var(--p-teal-bg);  color:var(--p-teal)}

.flash{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid;margin-bottom:16px}
.flash-ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden;margin-bottom:16px}
.card:last-child{margin-bottom:0}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:15px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:20px}

/* Layout */
.kal-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
@media(max-width:820px){.kal-grid{grid-template-columns:1fr}}

/* Noten */
.noten-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.noten-fach{
    background:var(--p-bg);border:1px solid var(--p-border);
    border-radius:var(--p-rsm);padding:14px 16px;
    transition:border-color .12s
}
.noten-fach:hover{border-color:var(--p-blue)}
.noten-fach-name{font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.noten-avg{font-size:28px;font-weight:700;letter-spacing:-.5px;color:var(--p-text);line-height:1;margin-bottom:10px}
.noten-history{display:flex;flex-direction:column;gap:3px}
.noten-history-row{display:flex;justify-content:space-between;font-size:11.5px;color:var(--p-muted)}
.noten-history-row .note-val{font-weight:600;color:var(--p-text)}
.gesamt-avg{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--p-muted)}
.gesamt-avg strong{font-size:20px;font-weight:700;color:var(--p-text)}

/* Termin rows */
.termin-row{
    display:flex;align-items:flex-start;justify-content:space-between;
    gap:12px;padding:13px 20px;
    border-bottom:1px solid var(--p-border);
    transition:background .1s
}
.termin-row:last-child{border-bottom:none}
.termin-row:hover{background:var(--p-gray-bg)}
.termin-fach{font-size:13.5px;font-weight:600;margin-bottom:4px}
.termin-thema{font-size:12.5px;color:var(--p-muted);margin-top:3px}
.termin-notizen{font-size:12px;color:var(--p-subtle);margin-top:4px;font-style:italic}
.termin-date{font-size:13px;font-weight:600;color:var(--p-text);white-space:nowrap}
.termin-rel{font-size:11.5px;color:var(--p-muted);margin-top:2px;text-align:right}
.termin-actions{display:flex;gap:4px;margin-top:6px;justify-content:flex-end}
.t-action-btn{
    display:inline-flex;align-items:center;gap:3px;
    padding:3px 7px;border-radius:var(--p-rsm);
    font-size:11.5px;font-weight:500;cursor:pointer;border:none;
    font-family:var(--p-font);background:transparent;transition:background .1s
}
.t-action-btn.edit{color:var(--p-blue)}.t-action-btn.edit:hover{background:var(--p-blue-bg)}
.t-action-btn.del {color:var(--p-red)} .t-action-btn.del:hover {background:var(--p-red-bg)}

/* Note badge on past entries */
.note-badge{
    display:inline-flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:8px;
    font-size:13px;font-weight:700;flex-shrink:0
}
.note-badge.good{background:var(--p-green-bg);color:var(--p-green)}
.note-badge.mid {background:var(--p-amber-bg);color:var(--p-amber)}
.note-badge.bad {background:var(--p-red-bg);  color:var(--p-red)}
.note-badge.none{background:var(--p-gray-bg); color:var(--p-subtle);font-size:10px}

/* Typ colors */
.typ-test        {background:var(--p-blue-bg);  color:var(--p-blue)}
.typ-klassenarbeit{background:var(--p-purple-bg);color:var(--p-purple)}
.typ-hausaufgabe {background:var(--p-teal-bg);  color:var(--p-teal)}
.typ-projekt     {background:var(--p-amber-bg); color:var(--p-amber)}
.typ-sonstiges   {background:var(--p-gray-bg);  color:var(--p-muted)}

/* Modals */
.modal-bd{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);align-items:center;justify-content:center;z-index:600;padding:20px}
.modal-bd.open{display:flex}
.modal{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:mIn .18s ease;max-height:90vh;overflow-y:auto}
@keyframes mIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--p-border);position:sticky;top:0;background:var(--p-surface);z-index:1}
.modal-title{font-size:15px;font-weight:700}
.modal-x{background:none;border:none;cursor:pointer;color:var(--p-muted);padding:4px;border-radius:4px;line-height:1}.modal-x:hover{color:var(--p-text);background:var(--p-gray-bg)}
.modal-body{padding:20px;display:flex;flex-direction:column;gap:14px}
.modal-ft{display:flex;justify-content:flex-end;gap:8px;padding:14px 20px;border-top:1px solid var(--p-border);background:var(--p-gray-bg);position:sticky;bottom:0}

.fg{display:flex;flex-direction:column;gap:5px}
.fl{font-size:12.5px;font-weight:600;color:var(--p-muted)}
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%;color-scheme:light}
html[data-theme="dark"] .fi{color-scheme:dark}
input[type="date"]::-webkit-calendar-picker-indicator{opacity:.6;filter:var(--date-icon-filter,none);cursor:pointer}
html[data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator{filter:invert(1);opacity:.7}
.fi:focus{border-color:var(--p-blue)}
textarea.fi{resize:vertical;min-height:68px}

/* Fächer liste */
.fach-row{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:var(--p-bg);border-radius:var(--p-rsm);font-size:13px;margin-bottom:6px}
.fach-row:last-child{margin-bottom:0}
</style>

<div class="kal-wrap">

    <div class="kal-topbar">
        <div class="kal-title">Mein Kalender</div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-ghost" onclick="openModal('modal-fach')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                Fächer verwalten
            </button>
            <button class="btn btn-primary" onclick="openModal('modal-erstellen')">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Neuer Eintrag
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="flash flash-ok">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Notenübersicht (full width) --}}
    @if($noten->count())
        <div class="card" style="margin-bottom:20px">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Notenübersicht
                </span>
                @if($gesamtdurchschnitt)
                    <span class="gesamt-avg">Gesamt-Ø <strong>{{ $gesamtdurchschnitt }}</strong></span>
                @endif
            </div>
            <div class="card-body">
                <div class="noten-grid">
                    @foreach($noten as $fach => $data)
                        <div class="noten-fach">
                            <div class="noten-fach-name">{{ $fach }}</div>
                            <div class="noten-avg">Ø {{ $data['durchschnitt'] }}</div>
                            <div class="noten-history">
                                @foreach($data['eintraege']->take(4) as $eintrag)
                                    <div class="noten-history-row">
                                        <span>{{ ucfirst($eintrag->typ) }} · {{ $eintrag->datum->format('d.m') }}</span>
                                        <span class="note-val">{{ $eintrag->note }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="kal-grid">

        {{-- ── LEFT: Kommende Termine ── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Kommende Termine
                </span>
                <span style="font-size:11.5px;color:var(--p-subtle)">{{ $kommend->count() }} Einträge</span>
            </div>

            @forelse($kommend as $eintrag)
                <div class="termin-row">
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:7px;margin-bottom:4px">
                            <span class="termin-fach">{{ $eintrag->fach }}</span>
                            <span class="pill {{ 'typ-'.$eintrag->typ }}" style="padding:2px 8px;font-size:11px">{{ ucfirst($eintrag->typ) }}</span>
                        </div>
                        @if($eintrag->thema)
                            <div class="termin-thema">{{ $eintrag->thema }}</div>
                        @endif
                        @if($eintrag->lernnotizen)
                            <div class="termin-notizen">📝 {{ Str::limit($eintrag->lernnotizen, 70) }}</div>
                        @endif
                    </div>
                    <div style="flex-shrink:0;text-align:right">
                        <div class="termin-date">{{ $eintrag->datum->format('d.m.Y') }}</div>
                        <div class="termin-rel">{{ $eintrag->datum->diffForHumans() }}</div>
                        <div class="termin-actions">
                            <button class="t-action-btn edit"
                                onclick="bearbeitenModal({{ $eintrag->id }}, '{{ addslashes($eintrag->fach) }}', '{{ $eintrag->typ }}', '{{ $eintrag->datum->format('Y-m-d') }}', '{{ addslashes($eintrag->thema ?? '') }}', '{{ addslashes($eintrag->lernnotizen ?? '') }}', '{{ $eintrag->note ?? '' }}')">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Bearbeiten
                            </button>
                            <form method="POST" action="{{ route('kalender.destroy', $eintrag->id) }}"
                                onsubmit="return confirm('Eintrag löschen?')">
                                @csrf @method('DELETE')
                                <button class="t-action-btn del" type="submit">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                    Löschen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:24px 20px;font-size:13px;color:var(--p-muted);text-align:center">Keine kommenden Termine.</div>
            @endforelse
        </div>

        {{-- ── RIGHT: Vergangene Termine ── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg>
                    Vergangene Termine
                </span>
                <span style="font-size:11.5px;color:var(--p-subtle)">{{ $vergangen->count() }}</span>
            </div>

            @forelse($vergangen as $eintrag)
                <div class="termin-row" style="gap:10px">
                    {{-- Note badge --}}
                    @if($eintrag->note)
                        @php
                            $n = $eintrag->note;
                            $cls = $n <= 2 ? 'good' : ($n <= 4 ? 'mid' : 'bad');
                        @endphp
                        <div class="note-badge {{ $cls }}">{{ $n }}</div>
                    @else
                        <div class="note-badge none">—</div>
                    @endif

                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px">
                            <span style="font-size:13px;font-weight:600">{{ $eintrag->fach }}</span>
                            <span class="pill {{ 'typ-'.$eintrag->typ }}" style="padding:2px 7px;font-size:10.5px">{{ ucfirst($eintrag->typ) }}</span>
                        </div>
                        @if($eintrag->thema)
                            <div style="font-size:12px;color:var(--p-muted)">{{ $eintrag->thema }}</div>
                        @endif
                        <div style="font-size:11.5px;color:var(--p-subtle);margin-top:2px">{{ $eintrag->datum->format('d.m.Y') }}</div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:3px;flex-shrink:0">
                        <button class="t-action-btn edit"
                            onclick="bearbeitenModal({{ $eintrag->id }}, '{{ addslashes($eintrag->fach) }}', '{{ $eintrag->typ }}', '{{ $eintrag->datum->format('Y-m-d') }}', '{{ addslashes($eintrag->thema ?? '') }}', '{{ addslashes($eintrag->lernnotizen ?? '') }}', '{{ $eintrag->note ?? '' }}')">
                            Bearbeiten
                        </button>
                        <form method="POST" action="{{ route('kalender.destroy', $eintrag->id) }}"
                            onsubmit="return confirm('Eintrag löschen?')">
                            @csrf @method('DELETE')
                            <button class="t-action-btn del" type="submit">Löschen</button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:24px 20px;font-size:13px;color:var(--p-muted);text-align:center">Keine vergangenen Termine.</div>
            @endforelse
        </div>

    </div>
</div>

{{-- ── Modal: Fächer verwalten ── --}}
<div id="modal-fach" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Fächer verwalten</span>
            <button class="modal-x" onclick="closeModal('modal-fach')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="modal-body">
            <form id="form-fach" style="display:flex;gap:8px">
                @csrf
                <input type="text" id="neues-fach" placeholder="Neues Fach..." class="fi" style="flex:1" required>
                <button type="submit" class="btn btn-primary" style="flex-shrink:0">Hinzufügen</button>
            </form>
            <div id="faecher-liste" style="display:flex;flex-direction:column;gap:6px">
                @forelse($faecher as $fach)
                    <div class="fach-row">
                        <span>{{ $fach->name }}</span>
                        <form method="POST" action="{{ route('kalender.fach.destroy', $fach->id) }}"
                            onsubmit="return confirm('Fach löschen?')">
                            @csrf @method('DELETE')
                            <button style="font-size:11.5px;color:var(--p-red);background:none;border:none;cursor:pointer;font-family:var(--p-font)">Löschen</button>
                        </form>
                    </div>
                @empty
                    <p style="font-size:13px;color:var(--p-muted)">Noch keine Fächer angelegt.</p>
                @endforelse
            </div>
        </div>
        <div class="modal-ft">
            <button class="btn btn-ghost" onclick="closeModal('modal-fach')">Schließen</button>
        </div>
    </div>
</div>

{{-- ── Modal: Neuer Eintrag ── --}}
<div id="modal-erstellen" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Neuer Eintrag</span>
            <button class="modal-x" onclick="closeModal('modal-erstellen')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form method="POST" action="{{ route('kalender.store') }}">
            @csrf
            <div class="modal-body">
                <div class="fg">
                    <label class="fl">Fach</label>
                    @if($faecher->count())
                        <select name="fach" id="fach-select-erstellen" class="fi" required>
                            <option value="">— Fach wählen —</option>
                            @foreach($faecher as $fach)
                                <option value="{{ $fach->name }}">{{ $fach->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <p style="font-size:12.5px;color:var(--p-muted)">Bitte zuerst ein Fach anlegen.</p>
                        <input type="hidden" name="fach" value="Allgemein">
                    @endif
                </div>
                <div class="fg">
                    <label class="fl">Typ</label>
                    <select name="typ" class="fi">
                        <option value="test">Test</option>
                        <option value="klassenarbeit">Klassenarbeit</option>
                        <option value="hausaufgabe">Hausaufgabe</option>
                        <option value="projekt">Projekt</option>
                        <option value="sonstiges">Sonstiges</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Datum</label>
                    <input type="date" name="datum" class="fi" required>
                </div>
                <div class="fg">
                    <label class="fl">Thema <span style="font-weight:400;color:var(--p-subtle)">(optional)</span></label>
                    <input type="text" name="thema" class="fi" placeholder="z.B. Kapitel 4 – Datenbanken">
                </div>
                <div class="fg">
                    <label class="fl">Lernnotizen <span style="font-weight:400;color:var(--p-subtle)">(optional)</span></label>
                    <textarea name="lernnotizen" class="fi" placeholder="Stichpunkte zum Lernen…"></textarea>
                </div>
                <div class="fg">
                    <label class="fl">Note <span style="font-weight:400;color:var(--p-subtle)">(optional, nach dem Termin)</span></label>
                    <input type="number" name="note" step="0.5" min="1" max="6" class="fi" placeholder="z.B. 1.5">
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" onclick="closeModal('modal-erstellen')" class="btn btn-ghost">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal: Bearbeiten ── --}}
<div id="modal-bearbeiten" class="modal-bd">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-title">Eintrag bearbeiten</span>
            <button class="modal-x" onclick="closeModal('modal-bearbeiten')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <form method="POST" id="form-bearbeiten">
            @csrf @method('PATCH')
            <div class="modal-body">
                <div class="fg">
                    <label class="fl">Fach</label>
                    @if($faecher->count())
                        <select name="fach" id="edit-fach" id="fach-select-bearbeiten" class="fi" required>
                            @foreach($faecher as $fach)
                                <option value="{{ $fach->name }}">{{ $fach->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="fach" id="edit-fach" class="fi" required>
                    @endif
                </div>
                <div class="fg">
                    <label class="fl">Typ</label>
                    <select name="typ" id="edit-typ" class="fi">
                        <option value="test">Test</option>
                        <option value="klassenarbeit">Klassenarbeit</option>
                        <option value="hausaufgabe">Hausaufgabe</option>
                        <option value="projekt">Projekt</option>
                        <option value="sonstiges">Sonstiges</option>
                    </select>
                </div>
                <div class="fg">
                    <label class="fl">Datum</label>
                    <input type="date" name="datum" id="edit-datum" class="fi" required>
                </div>
                <div class="fg">
                    <label class="fl">Thema</label>
                    <input type="text" name="thema" id="edit-thema" class="fi">
                </div>
                <div class="fg">
                    <label class="fl">Lernnotizen</label>
                    <textarea name="lernnotizen" id="edit-lernnotizen" class="fi"></textarea>
                </div>
                <div class="fg">
                    <label class="fl">Note <span style="font-weight:400;color:var(--p-subtle)">(optional)</span></label>
                    <input type="number" name="note" id="edit-note" step="0.5" min="1" max="6" class="fi">
                </div>
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

function bearbeitenModal(id, fach, typ, datum, thema, lernnotizen, note) {
    const fachEl = document.getElementById('edit-fach')
    fachEl.value = fach
    document.getElementById('edit-typ').value = typ
    document.getElementById('edit-datum').value = datum
    document.getElementById('edit-thema').value = thema
    document.getElementById('edit-lernnotizen').value = lernnotizen
    document.getElementById('edit-note').value = note
    document.getElementById('form-bearbeiten').action = '/kalender/' + id
    openModal('modal-bearbeiten')
}

document.getElementById('form-fach').addEventListener('submit', function(e) {
    e.preventDefault()
    const input = document.getElementById('neues-fach')
    const name = input.value.trim()
    if (!name) return
    fetch('{{ route('kalender.fach.store') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ name })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return
        // Add to list
        const liste = document.getElementById('faecher-liste')
        const div = document.createElement('div')
        div.className = 'fach-row'
        div.innerHTML = `<span>${name}</span><button onclick="fachLoeschen(${data.id},this)" style="font-size:11.5px;color:var(--p-red);background:none;border:none;cursor:pointer;font-family:var(--p-font)">Löschen</button>`
        liste.appendChild(div)
        // Update dropdowns
        ;['fach-select-erstellen','fach-select-bearbeiten'].forEach(selId => {
            const sel = document.getElementById(selId)
            if (sel) { const o = document.createElement('option'); o.value = name; o.textContent = name; sel.appendChild(o) }
        })
        input.value = ''
        input.focus()
    })
})

function fachLoeschen(id, btn) {
    if (!confirm('Fach löschen?')) return
    fetch(`/kalender/fach/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ _method: 'DELETE' })
    }).then(() => btn.closest('.fach-row').remove())
}
</script>

</x-app-layout>
