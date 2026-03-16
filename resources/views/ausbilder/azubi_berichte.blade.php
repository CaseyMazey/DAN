<x-app-layout>
<x-slot name="header"></x-slot>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap');
:root {
    --p-bg:#F4F7FB;--p-surface:#fff;--p-border:#E2E8F0;--p-text:#1A2233;--p-muted:#64748B;--p-subtle:#94A3B8;
    --p-blue:#2563EB;--p-blue-bg:#EEF3FF;--p-green:#16A34A;--p-green-bg:#DCFCE7;
    --p-amber:#D97706;--p-amber-bg:#FEF3C7;--p-red:#DC2626;--p-red-bg:#FEE2E2;
    --p-orange:#EA580C;--p-orange-bg:#FFF0E6;
    --p-radius:12px;--p-rsm:8px;--p-font:'DM Sans',system-ui,sans-serif;
}
html[data-theme="dark"] {
    --p-bg:#0D1117;--p-surface:#161B22;--p-border:#30363D;--p-text:#E6EDF3;--p-muted:#8B949E;--p-subtle:#484F58;
    --p-blue:#58A6FF;--p-blue-bg:#0D2340;--p-green:#3FB950;--p-green-bg:#0A2918;
    --p-amber:#E3B341;--p-amber-bg:#2D1F00;--p-red:#F85149;--p-red-bg:#2D0A0A;
    --p-orange:#F97316;--p-orange-bg:#2D1200;
}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}

.page{max-width:1100px;margin:0 auto;padding:28px 20px 48px;display:flex;flex-direction:column;gap:20px}
.page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.page-title{font-size:21px;font-weight:700;letter-spacing:-.4px}
.page-sub{font-size:13px;color:var(--p-muted);margin-top:3px}
.back-link{font-size:13px;color:var(--p-muted);text-decoration:none;display:flex;align-items:center;gap:4px;transition:color .15s}
.back-link:hover{color:var(--p-text)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;color:var(--p-text)}

.dan-table{width:100%;border-collapse:collapse}
.dan-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--p-border);background:var(--p-bg)}
.dan-table td{padding:12px 16px;font-size:13.5px;border-bottom:1px solid var(--p-border);vertical-align:middle}
.dan-table tr:last-child td{border-bottom:none}
.dan-table tbody tr:hover{background:var(--p-bg)}

.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}
.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}
.pill-orange{background:var(--p-orange-bg);color:var(--p-orange)}

.action-link{font-size:12.5px;font-weight:500;color:var(--p-blue);text-decoration:none;padding:5px 10px;border-radius:var(--p-rsm);background:var(--p-blue-bg);transition:opacity .15s;display:inline-block}
.action-link:hover{opacity:.8}

.empty{font-size:13.5px;color:var(--p-muted);padding:32px 16px;text-align:center}

/* Avatar */
.avatar{width:38px;height:38px;border-radius:50%;background:var(--p-blue-bg);color:var(--p-blue);font-weight:600;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
</style>

<div class="page">

    <div class="page-head">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="avatar">{{ strtoupper(substr($azubi->vorname,0,1).substr($azubi->nachname,0,1)) }}</div>
            <div>
                <div class="page-title">{{ $azubi->full_name }}</div>
                <div class="page-sub">{{ $berichte->count() }} Wochenbericht{{ $berichte->count() !== 1 ? 'e' : '' }}</div>
            </div>
        </div>
        <a href="{{ route('dashboard') }}" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Alle Wochenberichte</span>
        </div>
        @if($berichte->count())
            <table class="dan-table">
                <thead>
                    <tr>
                        <th>Kalenderwoche</th>
                        <th>Zeitraum</th>
                        <th>Status</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($berichte as $bericht)
                        <tr>
                            <td style="font-weight:600">KW {{ $bericht->startdatum->format('W / Y') }}</td>
                            <td style="color:var(--p-muted);font-size:12.5px">
                                {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                            </td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:4px">
                                    @if($bericht->gesperrt)
                                        <span class="pill pill-gray">🔒 Gesperrt</span>
                                    @elseif($bericht->signiert)
                                        <span class="pill pill-green">✓ Signiert</span>
                                    @elseif($bericht->azubi_signiert)
                                        <span class="pill pill-amber">⏳ Wartet auf dich</span>
                                    @elseif($bericht->abgegeben)
                                        <span class="pill pill-blue">Abgegeben</span>
                                    @else
                                        <span class="pill pill-gray">In Bearbeitung</span>
                                    @endif
                                    @if($bericht->bearbeitet)
                                        <span class="pill pill-orange">⚠ Bearbeitet</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('ausbilder.bericht.show', $bericht->id) }}" class="action-link">Ansehen</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Noch keine Berichte vorhanden.</div>
        @endif
    </div>

</div>
</x-app-layout>
