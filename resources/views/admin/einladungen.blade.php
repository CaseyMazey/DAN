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

.flash{padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid;word-break:break-all}
.flash.ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash.err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600}
.card-body{padding:20px}

.field-label{font-size:12px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:8px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%}
.fi:focus{border-color:var(--p-blue)}

.form-grid{display:grid;grid-template-columns:1fr 160px 180px;gap:12px;align-items:end}
@media(max-width:680px){.form-grid{grid-template-columns:1fr}}

.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:var(--p-rsm);font-size:13.5px;font-weight:500;font-family:var(--p-font);cursor:pointer;border:none;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-blue{background:var(--p-blue);color:#fff}
.btn-gray{background:var(--p-border);color:var(--p-text)}

.dan-table{width:100%;border-collapse:collapse}
.dan-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--p-border);background:var(--p-bg)}
.dan-table td{padding:11px 16px;font-size:13.5px;border-bottom:1px solid var(--p-border);vertical-align:middle}
.dan-table tr:last-child td{border-bottom:none}
.dan-table tbody tr:hover{background:var(--p-bg)}

.pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}
.pill-red{background:var(--p-red-bg);color:var(--p-red)}
.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}

.action-del{font-size:12.5px;font-weight:500;padding:5px 10px;border-radius:var(--p-rsm);border:none;cursor:pointer;font-family:var(--p-font);background:var(--p-red-bg);color:var(--p-red);transition:opacity .15s}
.action-del:hover{opacity:.8}

.empty{text-align:center;color:var(--p-muted);font-size:13.5px;padding:32px 16px}
</style>

<div class="page">

    <div class="page-head">
        <div>
            <div class="page-title">Einladungen</div>
            <div class="page-sub">Nutzer per Link einladen</div>
        </div>
    </div>

    @if(session('success'))<div class="flash ok">✓ {{ session('success') }}</div>@endif
    @if(session('error'))<div class="flash err">✕ {{ session('error') }}</div>@endif

    {{-- Neue Einladung --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Neue Einladung senden</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.einladungen.senden') }}">
                @csrf
                <div class="form-grid" style="margin-bottom:12px">
                    <div>
                        <div class="field-label">E-Mail</div>
                        <input type="email" name="email" class="fi" placeholder="person@beispiel.de" required>
                    </div>
                    <div>
                        <div class="field-label">Rolle</div>
                        <select name="rolle" id="rolle-select" class="fi" onchange="toggleKlasse()">
                            <option value="azubi">Azubi</option>
                            <option value="ausbilder">Ausbilder</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="klasse-field">
                        <div class="field-label">Klasse</div>
                        <select name="klasse_id" class="fi">
                            <option value="">— Keine —</option>
                            @foreach($klassen as $klasse)
                                <option value="{{ $klasse->id }}">{{ $klasse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-blue">✉ Einladung senden</button>
            </form>
        </div>
    </div>

    {{-- Liste --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Alle Einladungen</span>
            <span style="font-size:13px;color:var(--p-muted)">{{ $einladungen->count() }} gesamt</span>
        </div>
        @if($einladungen->count())
            <table class="dan-table">
                <thead>
                    <tr>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Klasse</th>
                        <th>Status</th>
                        <th>Läuft ab</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($einladungen as $einladung)
                        <tr>
                            <td style="font-weight:500">{{ $einladung->email }}</td>
                            <td>
                                @if($einladung->rolle === 'admin')
                                    <span class="pill pill-red">Admin</span>
                                @elseif($einladung->rolle === 'ausbilder')
                                    <span class="pill pill-blue">Ausbilder</span>
                                @else
                                    <span class="pill pill-green">Azubi</span>
                                @endif
                            </td>
                            <td style="color:var(--p-muted);font-size:13px">{{ $einladung->klasse?->name ?? '—' }}</td>
                            <td>
                                @if($einladung->istGenutzt())
                                    <span class="pill pill-green">✓ Genutzt</span>
                                @elseif($einladung->istAbgelaufen())
                                    <span class="pill pill-gray">Abgelaufen</span>
                                @else
                                    <span class="pill pill-amber">Ausstehend</span>
                                @endif
                            </td>
                            <td style="color:var(--p-muted);font-size:12.5px">
                                {{ $einladung->expires_at->format('d.m.Y H:i') }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.einladungen.loeschen', $einladung->id) }}"
                                    onsubmit="return confirm('Einladung löschen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-del">🗑</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Noch keine Einladungen vorhanden.</div>
        @endif
    </div>

</div>

<script>
function toggleKlasse() {
    const rolle = document.getElementById('rolle-select').value;
    document.getElementById('klasse-field').style.display = rolle === 'azubi' ? 'block' : 'none';
}
toggleKlasse();
</script>
</x-app-layout>
