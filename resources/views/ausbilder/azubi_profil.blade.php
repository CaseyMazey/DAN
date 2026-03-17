<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $azubi->full_name }}
            </h2>
            <a href="{{ url()->previous() }}" class="text-sm text-gray-500 hover:text-gray-700">← Zurück</a>
        </div>
    </x-slot>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
        :root {
            --p-bg:#F4F7FB; --p-surface:#FFFFFF; --p-border:#E2E8F0;
            --p-text:#1A2233; --p-muted:#64748B; --p-subtle:#94A3B8;
            --p-blue:#2563EB; --p-blue-bg:#EEF3FF;
            --p-green:#16A34A; --p-green-bg:#DCFCE7;
            --p-amber:#D97706; --p-amber-bg:#FEF3C7;
            --p-red:#DC2626; --p-red-bg:#FEE2E2;
            --p-orange:#C2410C; --p-orange-bg:#FFF7ED;
            --p-radius:12px; --p-rsm:8px;
            --p-font:'DM Sans',system-ui,sans-serif;
        }
        html[data-theme="dark"] {
            --p-bg:#0D1117; --p-surface:#161B22; --p-border:#30363D;
            --p-text:#E6EDF3; --p-muted:#8B949E; --p-subtle:#484F58;
            --p-blue:#58A6FF; --p-blue-bg:#0D2340;
            --p-green:#3FB950; --p-green-bg:#0A2918;
            --p-amber:#E3B341; --p-amber-bg:#2D1F00;
            --p-red:#F85149; --p-red-bg:#2D0A0A;
            --p-orange:#F97316; --p-orange-bg:#2A1500;
        }
        body { font-family: var(--p-font); background: var(--p-bg); color: var(--p-text); }
        .ap-page { max-width: 1100px; margin: 0 auto; padding: 28px 20px 60px; display: flex; flex-direction: column; gap: 24px; }
        .card { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); overflow: hidden; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 14px; border-bottom: 1px solid var(--p-border); }
        .card-title { font-size: 14px; font-weight: 600; color: var(--p-text); display: flex; align-items: center; gap: 7px; }

        /* Stat-Karten */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media(max-width:700px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
        .stat-card { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); padding: 18px 20px; }
        .stat-val { font-size: 28px; font-weight: 700; color: var(--p-text); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--p-muted); margin-top: 5px; }

        /* Noten */
        .noten-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; padding: 16px 20px; }
        .fach-card { background: var(--p-bg); border: 1px solid var(--p-border); border-radius: var(--p-rsm); padding: 14px 16px; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .fach-card:hover { border-color: var(--p-blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
        .fach-name { font-size: 12px; font-weight: 600; color: var(--p-muted); text-transform: uppercase; letter-spacing: .4px; }
        .fach-avg { font-size: 26px; font-weight: 700; color: var(--p-text); margin: 4px 0 8px; }
        .fach-last { font-size: 11.5px; color: var(--p-muted); border-top: 1px solid var(--p-border); padding-top: 8px; display: flex; flex-direction: column; gap: 3px; }
        .note-dot { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; font-size: 10px; font-weight: 700; background: var(--p-blue-bg); color: var(--p-blue); flex-shrink: 0; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 900; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--p-surface); border-radius: var(--p-radius); border: 1px solid var(--p-border); width: 100%; max-width: 520px; max-height: 80vh; display: flex; flex-direction: column; }
        .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--p-border); }
        .modal-title { font-size: 15px; font-weight: 600; color: var(--p-text); }
        .modal-close { background: none; border: none; cursor: pointer; color: var(--p-muted); padding: 4px; border-radius: 6px; }
        .modal-close:hover { background: var(--p-bg); color: var(--p-text); }
        .modal-body { overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 8px; }
        .modal-eintrag { display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: var(--p-bg); border-radius: var(--p-rsm); }
        .modal-meta { flex: 1; }
        .modal-typ { font-size: 12.5px; font-weight: 500; color: var(--p-text); }
        .modal-datum { font-size: 11.5px; color: var(--p-muted); margin-top: 1px; }
        .modal-thema { font-size: 11.5px; color: var(--p-muted); font-style: italic; margin-top: 2px; }

        /* Filter */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; padding: 16px 20px; border-bottom: 1px solid var(--p-border); align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--p-muted); }
        .filter-select { padding: 7px 10px; border: 1px solid var(--p-border); border-radius: var(--p-rsm); background: var(--p-bg); color: var(--p-text); font-size: 13px; font-family: var(--p-font); cursor: pointer; }
        .filter-select:focus { outline: none; border-color: var(--p-blue); }
        .months-wrap { display: flex; flex-wrap: wrap; gap: 4px; }
        .month-btn { padding: 5px 9px; border: 1px solid var(--p-border); border-radius: 6px; font-size: 12px; font-family: var(--p-font); background: var(--p-bg); color: var(--p-muted); cursor: pointer; transition: all .1s; }
        .month-btn.sel { background: var(--p-blue); color: #fff; border-color: var(--p-blue); }
        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: var(--p-rsm); font-size: 12.5px; font-weight: 500; cursor: pointer; border: none; font-family: var(--p-font); transition: opacity .12s; }
        .btn:hover { opacity: .85; }
        .btn-blue { background: var(--p-blue); color: #fff; }
        .btn-gray { background: var(--p-border); color: var(--p-text); }
        .btn:disabled { opacity: .35; cursor: not-allowed; }
        .filter-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; align-self: flex-end; }

        /* Berichte-Tabelle */
        .b-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .b-table thead th { padding: 9px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--p-muted); background: var(--p-bg); border-bottom: 1px solid var(--p-border); }
        .b-table tbody td { padding: 11px 16px; border-bottom: 1px solid var(--p-border); vertical-align: middle; }
        .b-table tbody tr:last-child td { border-bottom: none; }
        .b-table tbody tr:hover td { background: var(--p-bg); }
        .pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 99px; font-size: 11.5px; font-weight: 500; }
        .pill-green  { background: var(--p-green-bg);  color: var(--p-green); }
        .pill-amber  { background: var(--p-amber-bg);  color: var(--p-amber); }
        .pill-blue   { background: var(--p-blue-bg);   color: var(--p-blue); }
        .pill-orange { background: var(--p-orange-bg); color: var(--p-orange); }
        .pill-gray   { background: var(--p-border);    color: var(--p-muted); }
        #keine-ergebnisse { display: none; padding: 24px; text-align: center; color: var(--p-muted); font-size: 13px; }
    </style>

    <div class="ap-page">

        {{-- Statistiken --}}
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val">{{ $stats['gesamt'] }}</div>
                <div class="stat-label">Berichte gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color:var(--p-green)">{{ $stats['signiert'] }}</div>
                <div class="stat-label">Signiert</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color:var(--p-amber)">{{ $stats['ausstehend'] }}</div>
                <div class="stat-label">Warten auf Signatur</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color:var(--p-orange)">{{ $stats['bearbeitet'] }}</div>
                <div class="stat-label">Nachträglich bearbeitet</div>
            </div>
        </div>

        {{-- Noten --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Notenübersicht
                </span>
                @if($notenProFach->count())
                    <span style="font-size:13px;color:var(--p-muted)">
                        Gesamt-Ø
                        <strong style="color:var(--p-text);font-size:15px;margin-left:4px">
                            {{ round($notenProFach->avg('durchschnitt'), 2) }}
                        </strong>
                    </span>
                @endif
            </div>

            @if($notenProFach->count())
                <div class="noten-grid">
                    @foreach($notenProFach as $fachName => $data)
                        <div class="fach-card" onclick="openModal('{{ addslashes($fachName) }}')">
                            <div class="fach-name">{{ $fachName }}</div>
                            <div class="fach-avg">Ø {{ $data['durchschnitt'] }}</div>
                            <div class="fach-last">
                                @foreach($data['eintraege']->take(2) as $e)
                                    <div style="display:flex;align-items:center;gap:6px">
                                        <span class="note-dot">{{ $e->note }}</span>
                                        <span>{{ $e->typ }} · {{ $e->datum->format('d.m') }}</span>
                                    </div>
                                @endforeach
                                @if($data['eintraege']->count() > 2)
                                    <span style="color:var(--p-subtle);font-size:11px">+{{ $data['eintraege']->count() - 2 }} weitere</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding:20px;color:var(--p-muted);font-size:13px">Keine Noteneinträge vorhanden.</div>
            @endif
        </div>

        {{-- Berichte --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Wochenberichte
                </span>
            </div>

            {{-- Filter-Bar --}}
            <div class="filter-bar">
                <div class="filter-group">
                    <div class="filter-label">Jahr</div>
                    <select class="filter-select" id="filter-jahr" onchange="filterUpdate()">
                        <option value="">Alle Jahre</option>
                        @foreach($jahre as $jahr)
                            <option value="{{ $jahr }}">{{ $jahr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Monate</div>
                    <div class="months-wrap" id="months-wrap">
                        @foreach(['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'] as $i => $m)
                            <button class="month-btn" data-month="{{ $i + 1 }}" onclick="toggleMonth(this)">{{ $m }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Azubi signiert</div>
                    <div style="display:flex;gap:4px">
                        <button class="month-btn" id="az-ja" data-sig="azubi" data-val="ja" onclick="toggleSig(this)">Ja</button>
                        <button class="month-btn" id="az-nein" data-sig="azubi" data-val="nein" onclick="toggleSig(this)">Nein</button>
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-label">Ausbilder signiert</div>
                    <div style="display:flex;gap:4px">
                        <button class="month-btn" id="au-ja" data-sig="ausbilder" data-val="ja" onclick="toggleSig(this)">Ja</button>
                        <button class="month-btn" id="au-nein" data-sig="ausbilder" data-val="nein" onclick="toggleSig(this)">Nein</button>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-gray" onclick="filterReset()">Zurücksetzen</button>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-sammel" style="display:inline">
                        @csrf
                        <input type="hidden" name="azubi_id" value="{{ $azubi->id }}">
                        <input type="hidden" name="ids" id="sammel-ids">
                        <button type="submit" class="btn btn-blue" id="btn-sammel" onclick="setSammelIds()">
                            📄 Sammel-PDF
                        </button>
                    </form>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-hj1" style="display:inline">
                        @csrf
                        <input type="hidden" name="azubi_id" value="{{ $azubi->id }}">
                        <input type="hidden" name="ids" id="hj1-ids">
                        <button type="submit" class="btn btn-gray" id="btn-hj1" disabled
                                style="opacity:.35;cursor:not-allowed"
                                onclick="return setHalbjahrIds(1)">
                            1. Halbjahr PDF
                        </button>
                    </form>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-hj2" style="display:inline">
                        @csrf
                        <input type="hidden" name="azubi_id" value="{{ $azubi->id }}">
                        <input type="hidden" name="ids" id="hj2-ids">
                        <button type="submit" class="btn btn-gray" id="btn-hj2" disabled
                                style="opacity:.35;cursor:not-allowed"
                                onclick="return setHalbjahrIds(2)">
                            2. Halbjahr PDF
                        </button>
                    </form>
                </div>
            </div>

            <div style="overflow-x:auto">
                <table class="b-table" id="berichte-table">
                    <thead>
                        <tr>
                            <th>KW</th>
                            <th>Zeitraum</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($berichte as $bericht)
                            <tr data-jahr="{{ $bericht->startdatum->year }}"
                                data-monat="{{ $bericht->startdatum->month }}"
                                data-id="{{ $bericht->id }}"
                                data-azubi-sig="{{ $bericht->azubi_signiert ? 'ja' : 'nein' }}"
                                data-ausbilder-sig="{{ $bericht->signiert ? 'ja' : 'nein' }}">
                                <td style="font-weight:500">KW {{ $bericht->startdatum->format('W / Y') }}</td>
                                <td style="color:var(--p-muted);font-size:12px">
                                    {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                                </td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                                        @if($bericht->gesperrt)
                                            <span class="pill pill-gray">🔒 Gesperrt</span>
                                        @elseif($bericht->signiert)
                                            <span class="pill pill-green">✅ Signiert</span>
                                        @elseif($bericht->azubi_signiert)
                                            <span class="pill pill-amber">⏳ Wartet auf dich</span>
                                        @elseif($bericht->abgegeben)
                                            <span class="pill pill-blue">📤 Abgegeben</span>
                                        @else
                                            <span class="pill pill-gray">✏️ In Bearbeitung</span>
                                        @endif
                                        @if($bericht->bearbeitet)
                                            <span class="pill pill-orange">⚠️ Bearbeitet</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('ausbilder.bericht.show', $bericht->id) }}"
                                       style="padding:5px 10px;background:var(--p-blue-bg);color:var(--p-blue);border-radius:6px;font-size:12px;text-decoration:none;white-space:nowrap">
                                        Ansehen
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--p-muted)">Keine Berichte vorhanden.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="keine-ergebnisse">Keine Berichte für diese Filterauswahl.</div>
            </div>
        </div>
    </div>

    {{-- Noten-Modal --}}
    <div class="modal-overlay" id="notenModal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div class="modal-head">
                <span class="modal-title" id="modal-fach-name">Fach</span>
                <button class="modal-close" onclick="closeModal()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="modal-body" id="modal-body"></div>
        </div>
    </div>

    <script>
    // ─── Noten-Daten ───────────────────────────────────────────
    @php
        $notenJs = $notenProFach->map(function($d) {
            return [
                'eintraege' => $d['eintraege']->map(function($e) {
                    return [
                        'typ'   => $e->typ,
                        'datum' => $e->datum->format('d.m.Y'),
                        'note'  => $e->note,
                        'thema' => $e->thema,
                    ];
                })->values(),
                'durchschnitt' => $d['durchschnitt'],
            ];
        });
    @endphp
    const notenDaten = @json($notenJs);

    function openModal(fach) {
        const data = notenDaten[fach];
        if (!data) return;
        document.getElementById('modal-fach-name').textContent = fach + ' — Ø ' + data.durchschnitt;
        const body = document.getElementById('modal-body');
        body.innerHTML = '';
        data.eintraege.forEach(e => {
            const noteColor = e.note <= 2 ? 'var(--p-green)' : e.note <= 3 ? 'var(--p-blue)' : e.note <= 4 ? 'var(--p-amber)' : 'var(--p-red)';
            body.innerHTML += `
                <div class="modal-eintrag">
                    <div style="width:32px;height:32px;border-radius:50%;background:${noteColor}20;color:${noteColor};display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0">${e.note}</div>
                    <div class="modal-meta">
                        <div class="modal-typ">${e.typ}</div>
                        <div class="modal-datum">${e.datum}</div>
                        ${e.thema ? `<div class="modal-thema">${e.thema}</div>` : ''}
                    </div>
                </div>`;
        });
        document.getElementById('notenModal').classList.add('open');
    }
    function closeModal() {
        document.getElementById('notenModal').classList.remove('open');
    }

    // ─── Filter ────────────────────────────────────────────────
    let selectedMonths = new Set();
    let sigFilter = { azubi: null, ausbilder: null }; // null = egal, 'ja'/'nein'

    function toggleMonth(btn) {
        const m = parseInt(btn.dataset.month);
        if (selectedMonths.has(m)) { selectedMonths.delete(m); btn.classList.remove('sel'); }
        else { selectedMonths.add(m); btn.classList.add('sel'); }
        filterUpdate();
    }

    function toggleSig(btn) {
        const type = btn.dataset.sig;
        const val  = btn.dataset.val;
        if (sigFilter[type] === val) {
            sigFilter[type] = null;
            btn.classList.remove('sel');
        } else {
            sigFilter[type] = val;
            document.querySelectorAll(`[data-sig="${type}"]`).forEach(b => b.classList.remove('sel'));
            btn.classList.add('sel');
        }
        filterUpdate();
    }

    function filterUpdate() {
        const jahr = document.getElementById('filter-jahr').value;
        const rows = document.querySelectorAll('#berichte-table tbody tr[data-id]');
        let visible = 0;

        rows.forEach(row => {
            const rJahr = row.dataset.jahr;
            const rMonat = parseInt(row.dataset.monat);
            const jahrOk = !jahr || rJahr === jahr;
            const monatOk = selectedMonths.size === 0 || selectedMonths.has(rMonat);
            const azubiOk = !sigFilter.azubi || row.dataset.azubiSig === sigFilter.azubi;
            const ausbilderOk = !sigFilter.ausbilder || row.dataset.ausbilderSig === sigFilter.ausbilder;
            const show = jahrOk && monatOk && azubiOk && ausbilderOk;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('keine-ergebnisse').style.display = visible === 0 ? 'block' : 'none';

        const hj = !!jahr;
        ['btn-hj1','btn-hj2'].forEach(id => {
            const btn = document.getElementById(id);
            btn.disabled = !hj;
            btn.style.opacity = hj ? '1' : '.35';
            btn.style.cursor = hj ? 'pointer' : 'not-allowed';
        });
    }

    function setHalbjahrIds(hj) {
        if (document.getElementById('btn-hj' + hj).disabled) return false;
        const jahr = document.getElementById('filter-jahr').value;
        const monate = hj === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
        const ids = [...document.querySelectorAll('#berichte-table tbody tr[data-id]')]
            .filter(r => r.dataset.jahr === jahr && monate.includes(parseInt(r.dataset.monat)))
            .map(r => r.dataset.id);
        document.getElementById('hj' + hj + '-ids').value = ids.join(',');
        return true;
    }
    </script>
</x-app-layout>
