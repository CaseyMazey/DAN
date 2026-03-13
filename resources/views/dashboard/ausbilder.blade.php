<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');

:root {
    --p-bg: #F4F7FB; --p-surface: #FFFFFF; --p-border: #E2E8F0;
    --p-text: #1A2233; --p-muted: #64748B; --p-subtle: #94A3B8;
    --p-blue: #2563EB; --p-blue-bg: #EEF3FF;
    --p-green: #16A34A; --p-green-bg: #DCFCE7;
    --p-amber: #D97706; --p-amber-bg: #FEF3C7;
    --p-red: #DC2626; --p-red-bg: #FEE2E2;
    --p-orange: #C2410C; --p-orange-bg: #FFF7ED;
    --p-radius: 12px; --p-rsm: 8px;
    --p-font: 'DM Sans', system-ui, sans-serif;
}
html[data-theme="dark"] {
    --p-bg: #0D1117; --p-surface: #161B22; --p-border: #30363D;
    --p-text: #E6EDF3; --p-muted: #8B949E; --p-subtle: #484F58;
    --p-blue: #58A6FF; --p-blue-bg: #0D2340;
    --p-green: #3FB950; --p-green-bg: #0A2918;
    --p-amber: #E3B341; --p-amber-bg: #2D1F00;
    --p-red: #F85149; --p-red-bg: #2D0A0A;
    --p-orange: #F97316; --p-orange-bg: #2A1500;
}

body { font-family: var(--p-font); background: var(--p-bg); color: var(--p-text); }

.dash-page { max-width: 1100px; margin: 0 auto; padding: 28px 20px 48px; display: flex; flex-direction: column; gap: 20px; }

.dash-heading { font-size: 22px; font-weight: 700; letter-spacing: -.4px; color: var(--p-text); }
.dash-sub { font-size: 13.5px; color: var(--p-muted); margin-top: 2px; }

.card { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 12px; border-bottom: 1px solid var(--p-border); }
.card-title { font-size: 14px; font-weight: 600; color: var(--p-text); display: flex; align-items: center; gap: 7px; }
.card-link { font-size: 12.5px; font-weight: 500; color: var(--p-blue); text-decoration: none; }
.card-link:hover { opacity: .75; }

.pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 99px; font-size: 11.5px; font-weight: 500; }
.pill-blue   { background: var(--p-blue-bg);   color: var(--p-blue); }
.pill-green  { background: var(--p-green-bg);  color: var(--p-green); }
.pill-amber  { background: var(--p-amber-bg);  color: var(--p-amber); }
.pill-orange { background: var(--p-orange-bg); color: var(--p-orange); }
.pill-gray   { background: var(--p-border);    color: var(--p-muted); }

/* Signature list */
.sig-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--p-border);
    transition: background .1s;
}
.sig-item:last-child { border-bottom: none; }
.sig-item:hover { background: var(--p-bg); }
.sig-name { font-size: 13.5px; font-weight: 500; color: var(--p-text); }
.sig-meta { font-size: 12px; color: var(--p-muted); margin-top: 1px; }
.sig-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

.btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: var(--p-rsm);
    font-size: 12.5px;
    font-weight: 500;
    text-decoration: none;
    transition: all .12s;
    cursor: pointer;
    border: none;
    font-family: var(--p-font);
}
.btn-primary { background: var(--p-blue); color: #fff; }
.btn-primary:hover { opacity: .88; }

/* Klassen table */
.dan-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.dan-table thead th {
    padding: 9px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--p-muted);
    background: var(--p-bg);
    border-bottom: 1px solid var(--p-border);
}
.dan-table tbody td { padding: 11px 16px; border-bottom: 1px solid var(--p-border); vertical-align: middle; }
.dan-table tbody tr:last-child td { border-bottom: none; }
.dan-table tbody tr:hover td { background: var(--p-bg); }
.td-name { font-weight: 500; color: var(--p-text); }
.td-muted { color: var(--p-muted); font-size: 12px; }

.empty-state { font-size: 13px; color: var(--p-muted); padding: 16px 20px; }

/* Klasse section heading */
.klasse-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px 12px;
    border-bottom: 1px solid var(--p-border);
    background: var(--p-surface);
}
.klasse-name { font-size: 15px; font-weight: 600; color: var(--p-text); text-decoration: none; }
.klasse-name:hover { color: var(--p-blue); }
.klasse-meta { font-size: 12px; color: var(--p-muted); font-weight: 400; margin-left: 8px; }
</style>

<div class="dash-page">

    {{-- Page heading --}}
    <div>
        <div class="dash-heading">Ausbilder-Dashboard</div>
        <div class="dash-sub">{{ now()->format('l, d. F Y') }}</div>
    </div>

    {{-- Ausstehende Signaturen --}}
    @if($ausstehendSignaturen->count())
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--p-amber)" stroke-width="2" stroke-linecap="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Ausstehende Signaturen
                <span class="pill pill-amber">{{ $ausstehendSignaturen->count() }}</span>
            </span>
        </div>
        @foreach($ausstehendSignaturen as $bericht)
            <div class="sig-item">
                <div>
                    <div class="sig-name">{{ $bericht->azubi->full_name }}</div>
                    <div class="sig-meta">
                        KW {{ $bericht->startdatum->format('W / Y') }} —
                        {{ $bericht->startdatum->format('d.m.Y') }} bis {{ $bericht->enddatum->format('d.m.Y') }}
                    </div>
                </div>
                <div class="sig-actions">
                    @if($bericht->bearbeitet)
                        <span class="pill pill-orange">⚠ Bearbeitet</span>
                    @endif
                    <a href="{{ route('ausbilder.bericht.show', $bericht->id) }}" class="btn-sm btn-primary">
                        Ansehen & Signieren
                    </a>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Klassen --}}
    @forelse($klassenUebersicht as $item)
        <div class="card">
            <div class="klasse-heading">
                <div>
                    <a href="{{ route('ausbilder.klasse.show', $item['klasse']->id) }}" class="klasse-name">
                        {{ $item['klasse']->name }}
                    </a>
                    @if($item['klasse']->jahrgang)
                        <span class="klasse-meta">Jahrgang {{ $item['klasse']->jahrgang }}</span>
                    @endif
                </div>
                <span class="pill pill-gray">{{ $item['azubis']->count() }} Azubis</span>
            </div>

            @if($item['azubis']->count())
                <div style="overflow-x:auto">
                    <table class="dan-table">
                        <thead>
                            <tr>
                                <th>Azubi</th>
                                <th>Letzter Bericht</th>
                                <th>Status</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item['azubis'] as $eintrag)
                                <tr>
                                    <td class="td-name">{{ $eintrag['azubi']->full_name }}</td>
                                    <td class="td-muted">
                                        @if($eintrag['letzterBericht'])
                                            KW {{ $eintrag['letzterBericht']->startdatum->format('W / Y') }}
                                        @else —
                                        @endif
                                    </td>
                                    <td>
                                        @if($eintrag['letzterBericht'])
                                            @if($eintrag['letzterBericht']->signiert)
                                                <span class="pill pill-green">✓ Signiert</span>
                                            @elseif($eintrag['letzterBericht']->azubi_signiert)
                                                <span class="pill pill-amber">⏳ Wartet auf dich</span>
                                            @elseif($eintrag['letzterBericht']->abgegeben)
                                                <span class="pill pill-blue">↑ Abgegeben</span>
                                            @else
                                                <span class="pill pill-gray">✏ In Bearbeitung</span>
                                            @endif
                                            @if($eintrag['letzterBericht']->bearbeitet)
                                                <span class="pill pill-orange" style="margin-left:4px">⚠ Bearbeitet</span>
                                            @endif
                                        @else
                                            <span class="td-muted">Keine Berichte</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('ausbilder.azubi.berichte', $eintrag['azubi']->id) }}"
                                           class="btn-sm" style="background:var(--p-blue-bg);color:var(--p-blue)">
                                            Alle Berichte
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">Noch keine Azubis in dieser Klasse.</div>
            @endif
        </div>
    @empty
        <div class="card">
            <div class="empty-state">Du bist noch keiner Klasse zugewiesen.</div>
        </div>
    @endforelse

</div>
</x-app-layout>
