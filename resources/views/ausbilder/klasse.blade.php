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
.page-title{font-size:21px;font-weight:700;letter-spacing:-.4px;color:var(--p-text);display:flex;align-items:center;gap:8px}
.page-sub{font-size:13px;color:var(--p-muted);margin-top:3px}
.back-link{font-size:13px;color:var(--p-muted);text-decoration:none;display:flex;align-items:center;gap:4px;transition:color .15s}
.back-link:hover{color:var(--p-text)}

.flash{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid}
.flash.ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash.err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;color:var(--p-text)}
.card-body{padding:20px}

/* Add azubi form */
.add-row{display:flex;gap:10px}
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%}
.fi:focus{border-color:var(--p-blue)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--p-rsm);font-size:13.5px;font-weight:500;font-family:var(--p-font);cursor:pointer;border:none;transition:opacity .15s;white-space:nowrap}
.btn:hover{opacity:.85}
.btn-blue{background:var(--p-blue);color:#fff}
.btn-red{background:var(--p-red-bg);color:var(--p-red)}
.hint{font-size:12px;color:var(--p-subtle);margin-top:8px}

/* Table */
.dan-table{width:100%;border-collapse:collapse}
.dan-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--p-border);background:var(--p-bg)}
.dan-table td{padding:12px 16px;font-size:13.5px;border-bottom:1px solid var(--p-border);vertical-align:middle}
.dan-table tr:last-child td{border-bottom:none}
.dan-table tbody tr:hover{background:var(--p-bg)}
.name-main{font-weight:500;color:var(--p-text)}
.name-sub{font-size:12px;color:var(--p-subtle);margin-top:2px}

/* Pills */
.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}
.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}
.pill-orange{background:var(--p-orange-bg);color:var(--p-orange)}

.action-link{font-size:12.5px;font-weight:500;color:var(--p-blue);text-decoration:none;padding:5px 10px;border-radius:var(--p-rsm);background:var(--p-blue-bg);transition:opacity .15s;display:inline-block}
.action-link:hover{opacity:.8}
.action-del{font-size:12.5px;font-weight:500;color:var(--p-red);background:var(--p-red-bg);padding:5px 10px;border-radius:var(--p-rsm);border:none;cursor:pointer;font-family:var(--p-font);transition:opacity .15s}
.action-del:hover{opacity:.8}

.empty{font-size:13.5px;color:var(--p-muted);padding:24px 0;text-align:center}
</style>

<div class="page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div class="page-title">
                {{ $klasse->name }}
                @if($klasse->jahrgang)
                    <span style="font-size:13px;font-weight:400;color:var(--p-muted)">Jahrgang {{ $klasse->jahrgang }}</span>
                @endif
            </div>
            <div class="page-sub">{{ $azubis->count() }} Azubi{{ $azubis->count() !== 1 ? 's' : '' }} in dieser Klasse</div>
        </div>
        <a href="{{ route('dashboard') }}" class="back-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Dashboard
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash ok">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash err">✕ {{ session('error') }}</div>
    @endif

    {{-- Azubi hinzufügen --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Azubi hinzufügen oder einladen</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('ausbilder.klasse.azubi.hinzufuegen', $klasse->id) }}">
                @csrf
                <div class="add-row">
                    <input type="email" name="email" class="fi" placeholder="E-Mail-Adresse des Azubis" required>
                    <button type="submit" class="btn btn-blue">Hinzufügen</button>
                </div>
            </form>
            <div class="hint">Existiert bereits ein Account, wird er direkt hinzugefügt. Ansonsten erhält die Person einen Einladungslink (7 Tage gültig).</div>
        </div>
    </div>

    {{-- Azubi-Liste --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Azubis</span>
            <span style="font-size:13px;color:var(--p-muted)">{{ $azubis->count() }} Einträge</span>
        </div>
        @if($azubis->count())
            <table class="dan-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Ausbildung</th>
                        <th>Letzter Bericht</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($azubis as $eintrag)
                        <tr>
                            <td>
                                <div class="name-main">{{ $eintrag['azubi']->full_name }}</div>
                                <div class="name-sub">{{ $eintrag['azubi']->email }}</div>
                            </td>
                            <td style="font-size:12.5px;color:var(--p-muted)">
                                @if($eintrag['azubi']->ausbildungsbeginn)
                                    {{ $eintrag['azubi']->ausbildungsbeginn->format('d.m.Y') }}
                                    @if($eintrag['azubi']->ausbildungsende)
                                        <span style="color:var(--p-subtle)">–</span> {{ $eintrag['azubi']->ausbildungsende->format('d.m.Y') }}
                                    @endif
                                @else
                                    <span style="color:var(--p-subtle)">—</span>
                                @endif
                            </td>
                            <td style="font-size:13px;color:var(--p-muted)">
                                @if($eintrag['letzterBericht'])
                                    KW {{ $eintrag['letzterBericht']->startdatum->format('W / Y') }}
                                @else
                                    <span style="color:var(--p-subtle)">—</span>
                                @endif
                            </td>
                            <td>
                                @if($eintrag['letzterBericht'])
                                    @if($eintrag['letzterBericht']->gesperrt)
                                        <span class="pill pill-gray">🔒 Gesperrt</span>
                                    @elseif($eintrag['letzterBericht']->signiert)
                                        <span class="pill pill-green">✓ Signiert</span>
                                    @elseif($eintrag['letzterBericht']->azubi_signiert)
                                        <span class="pill pill-amber">⏳ Wartet auf dich</span>
                                    @elseif($eintrag['letzterBericht']->abgegeben)
                                        <span class="pill pill-blue">Abgegeben</span>
                                    @else
                                        <span class="pill pill-gray">In Bearbeitung</span>
                                    @endif
                                    @if($eintrag['letzterBericht']->bearbeitet)
                                        <span class="pill pill-orange" style="margin-left:4px">⚠ Bearbeitet</span>
                                    @endif
                                @else
                                    <span style="font-size:12.5px;color:var(--p-subtle)">Keine Berichte</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;align-items:center">
                                    <a href="{{ route('ausbilder.azubi.berichte', $eintrag['azubi']->id) }}" class="action-link">Berichte</a>
                                    <form method="POST" action="{{ route('ausbilder.klasse.azubi.entfernen', [$klasse->id, $eintrag['azubi']->id]) }}"
                                        onsubmit="return confirm('{{ $eintrag['azubi']->full_name }} aus der Klasse entfernen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-del">Entfernen</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Noch keine Azubis in dieser Klasse.</div>
        @endif
    </div>

</div>
</x-app-layout>
