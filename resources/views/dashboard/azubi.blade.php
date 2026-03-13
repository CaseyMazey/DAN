<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');

:root {
    --p-bg: #F4F7FB;
    --p-surface: #FFFFFF;
    --p-border: #E2E8F0;
    --p-text: #1A2233;
    --p-muted: #64748B;
    --p-subtle: #94A3B8;
    --p-blue: #2563EB;
    --p-blue-bg: #EEF3FF;
    --p-blue-light: #DBEAFE;
    --p-green: #16A34A;
    --p-green-bg: #DCFCE7;
    --p-amber: #D97706;
    --p-amber-bg: #FEF3C7;
    --p-red: #DC2626;
    --p-red-bg: #FEE2E2;
    --p-radius: 12px;
    --p-rsm: 8px;
    --p-font: 'DM Sans', system-ui, sans-serif;
}

html[data-theme="dark"] {
    --p-bg: #0D1117;
    --p-surface: #161B22;
    --p-border: #30363D;
    --p-text: #E6EDF3;
    --p-muted: #8B949E;
    --p-subtle: #484F58;
    --p-blue: #58A6FF;
    --p-blue-bg: #0D2340;
    --p-blue-light: #0D2340;
    --p-green: #3FB950;
    --p-green-bg: #0A2918;
    --p-amber: #E3B341;
    --p-amber-bg: #2D1F00;
    --p-red: #F85149;
    --p-red-bg: #2D0A0A;
}

body { font-family: var(--p-font); background: var(--p-bg); color: var(--p-text); }

.dash-page {
    max-width: 1100px;
    margin: 0 auto;
    padding: 28px 20px 48px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Page heading */
.dash-greeting {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.dash-greeting-name {
    font-size: 22px;
    font-weight: 700;
    color: var(--p-text);
    letter-spacing: -.4px;
}
.dash-greeting-sub {
    font-size: 13.5px;
    color: var(--p-muted);
    margin-top: 2px;
}

/* Pill badges */
.pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 500;
}
.pill-blue  { background: var(--p-blue-bg);  color: var(--p-blue); }
.pill-green { background: var(--p-green-bg); color: var(--p-green); }
.pill-amber { background: var(--p-amber-bg); color: var(--p-amber); }
.pill-red   { background: var(--p-red-bg);   color: var(--p-red); }

/* Alert banners */
.dash-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: var(--p-radius);
    border: 1px solid;
}
.dash-alert-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.dash-alert-title { font-size: 13.5px; font-weight: 600; color: var(--p-text); }
.dash-alert-sub   { font-size: 12.5px; margin-top: 2px; }
.dash-alert a { font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
.dash-alert.amber { background: var(--p-amber-bg); border-color: var(--p-amber); }
.dash-alert.amber .dash-alert-sub { color: var(--p-amber); }
.dash-alert.blue  { background: var(--p-blue-bg);  border-color: var(--p-blue); }
.dash-alert.blue  .dash-alert-sub { color: var(--p-muted); }

/* Grid layouts */
.dash-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.dash-grid-auto { display: grid; grid-template-columns: 200px 1fr; gap: 14px; }

@media (max-width: 640px) {
    .dash-grid-3 { grid-template-columns: 1fr; }
    .dash-grid-auto { grid-template-columns: 1fr; }
}

/* Cards */
.card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    overflow: hidden;
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px 0;
    margin-bottom: 14px;
}
.card-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--p-text);
    display: flex;
    align-items: center;
    gap: 7px;
}
.card-link {
    font-size: 12.5px;
    font-weight: 500;
    color: var(--p-blue);
    text-decoration: none;
    transition: opacity .15s;
}
.card-link:hover { opacity: .75; }
.card-body { padding: 0 20px 20px; }

/* Stat cards */
.stat-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: var(--p-rsm);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}
.stat-number { font-size: 32px; font-weight: 700; letter-spacing: -1px; color: var(--p-text); line-height: 1; }
.stat-label  { font-size: 12.5px; color: var(--p-muted); font-weight: 400; }

/* Hours big stat */
.hours-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 4px;
}
.hours-number { font-size: 52px; font-weight: 700; letter-spacing: -2px; color: var(--p-text); line-height: 1; }
.hours-label  { font-size: 12px; text-transform: uppercase; letter-spacing: .6px; font-weight: 500; color: var(--p-muted); margin-top: 6px; }

/* Termin rows */
.termin-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--p-border);
}
.termin-row:last-child { border-bottom: none; }
.termin-fach { font-size: 13.5px; font-weight: 500; color: var(--p-text); }
.termin-typ  { font-size: 12px; color: var(--p-muted); margin-top: 1px; }
.termin-datum { font-size: 12.5px; font-weight: 500; color: var(--p-text); text-align: right; }
.termin-rel  { font-size: 11.5px; color: var(--p-subtle); margin-top: 1px; }

.empty-state { font-size: 13px; color: var(--p-muted); padding: 10px 0; }
</style>

<div class="dash-page">

    {{-- Greeting --}}
    <div class="dash-greeting">
        <div>
            <div class="dash-greeting-name">Hallo, {{ auth()->user()->vorname }} 👋</div>
            <div class="dash-greeting-sub">{{ now()->format('l, d. F Y') }}</div>
        </div>
        @if(auth()->user()->ausbildungsbeginn && auth()->user()->ausbildungsende)
            @php
                $start = auth()->user()->ausbildungsbeginn;
                $end   = auth()->user()->ausbildungsende;
                $total = (int) $start->diffInWeeks($end);
                $done  = (int) $start->diffInWeeks(now());
                $pct   = min(100, round($done / max(1,$total) * 100));
            @endphp
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                <div style="font-size:12px;color:var(--p-muted);text-align:right">
                    Ausbildung<br><strong style="color:var(--p-text)">{{ $done }} / {{ $total }} Wochen</strong>
                </div>
                <div style="width:80px;height:6px;background:var(--p-border);border-radius:99px;overflow:hidden">
                    <div style="width:{{ $pct }}%;height:100%;background:var(--p-blue);border-radius:99px"></div>
                </div>
                <div style="font-size:12px;font-weight:600;color:var(--p-blue)">{{ $pct }}%</div>
            </div>
        @endif
    </div>

    {{-- Alerts --}}
    @if($nichtSigniert > 0)
        <div class="dash-alert amber">
            <div class="dash-alert-icon">✍️</div>
            <div>
                <div class="dash-alert-title">{{ $nichtSigniert }} Bericht{{ $nichtSigniert > 1 ? 'e' : '' }} warten auf deine Signatur</div>
                <div class="dash-alert-sub"><a href="{{ route('wochenbericht.index') }}" style="color:var(--p-amber)">Jetzt signieren →</a></div>
            </div>
        </div>
    @endif

    @if($ausstehendAusbilder > 0)
        <div class="dash-alert blue">
            <div class="dash-alert-icon">⏳</div>
            <div>
                <div class="dash-alert-title">{{ $ausstehendAusbilder }} Bericht{{ $ausstehendAusbilder > 1 ? 'e' : '' }} warten auf Ausbilder-Signatur</div>
                <div class="dash-alert-sub">Dein Ausbilder muss diese noch unterschreiben.</div>
            </div>
        </div>
    @endif

    {{-- Aufgaben Stats --}}
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <span style="font-size:13px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.5px">Aufgaben</span>
            <a href="{{ route('aufgaben.index') }}" class="card-link">Alle anzeigen →</a>
        </div>
        <div class="dash-grid-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--p-border)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-muted)" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="stat-number">{{ $aufgabenStats['geplant'] }}</div>
                <div class="stat-label">Geplant</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--p-blue-bg)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ 'var(--p-blue)' }}" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="stat-number" style="color:var(--p-blue)">{{ $aufgabenStats['in_bearbeitung'] }}</div>
                <div class="stat-label">In Bearbeitung</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--p-green-bg)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-green)" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="stat-number" style="color:var(--p-green)">{{ $aufgabenStats['fertig'] }}</div>
                <div class="stat-label">Fertig</div>
            </div>
        </div>
    </div>

    {{-- Stunden + Kalender --}}
    <div class="dash-grid-auto">

        {{-- Stunden --}}
        <div class="hours-card">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.7px;font-weight:600;color:var(--p-muted)">Stunden diese Woche</div>
            <div class="hours-number">{{ $stundenDieseWoche }}</div>
            <div class="hours-label">aktive Aufgaben</div>
        </div>

        {{-- Nächste Termine --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Nächste Termine
                </span>
                <a href="{{ route('kalender.index') }}" class="card-link">Alle →</a>
            </div>
            <div class="card-body">
                @forelse($naechsteTermine as $termin)
                    <div class="termin-row">
                        <div>
                            <div class="termin-fach">{{ $termin->fach }}</div>
                            <div class="termin-typ">{{ ucfirst($termin->typ) }}@if($termin->thema) — {{ $termin->thema }}@endif</div>
                        </div>
                        <div>
                            <div class="termin-datum">{{ $termin->datum->format('d.m.Y') }}</div>
                            <div class="termin-rel">{{ $termin->datum->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Keine kommenden Termine.</div>
                @endforelse
            </div>
        </div>
    </div>



</div>
</x-app-layout>
