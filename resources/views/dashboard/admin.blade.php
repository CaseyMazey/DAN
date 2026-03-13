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
}

body { font-family: var(--p-font); background: var(--p-bg); color: var(--p-text); }
.dash-page { max-width: 1100px; margin: 0 auto; padding: 28px 20px 48px; display: flex; flex-direction: column; gap: 20px; }

.dash-heading { font-size: 22px; font-weight: 700; letter-spacing: -.4px; color: var(--p-text); }
.dash-sub { font-size: 13.5px; color: var(--p-muted); margin-top: 2px; }

.card { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); }
.card-header { padding: 18px 20px 16px; border-bottom: 1px solid var(--p-border); }
.card-title { font-size: 14px; font-weight: 600; color: var(--p-text); }
.card-body { padding: 20px; }

.admin-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
@media (max-width: 640px) { .admin-grid { grid-template-columns: 1fr; } }

.admin-link-card {
    background: var(--p-surface);
    border: 1px solid var(--p-border);
    border-radius: var(--p-radius);
    padding: 22px 20px;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition: border-color .15s, box-shadow .15s;
}
.admin-link-card:hover { border-color: var(--p-blue); box-shadow: 0 0 0 3px var(--p-blue-bg); }
.admin-link-icon {
    width: 38px; height: 38px;
    border-radius: var(--p-rsm);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 4px;
}
.admin-link-title { font-size: 14px; font-weight: 600; color: var(--p-text); }
.admin-link-desc  { font-size: 12.5px; color: var(--p-muted); }
</style>

<div class="dash-page">

    <div>
        <div class="dash-heading">Admin-Dashboard</div>
        <div class="dash-sub">{{ now()->format('l, d. F Y') }}</div>
    </div>

    <div>
        <div style="font-size:13px;font-weight:600;color:var(--p-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Verwaltung</div>
        <div class="admin-grid">
            <a href="{{ route('admin.nutzer') }}" class="admin-link-card">
                <div class="admin-link-icon" style="background:var(--p-blue-bg)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-blue)" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="admin-link-title">Nutzerverwaltung</div>
                <div class="admin-link-desc">Rollen zuweisen, Nutzer deaktivieren</div>
            </a>
            <a href="{{ route('admin.klassen') }}" class="admin-link-card">
                <div class="admin-link-icon" style="background:var(--p-green-bg)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-green)" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div class="admin-link-title">Klassen</div>
                <div class="admin-link-desc">Klassen erstellen und verwalten</div>
            </a>
            <a href="{{ route('admin.einladungen') }}" class="admin-link-card">
                <div class="admin-link-icon" style="background:var(--p-amber-bg)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--p-amber)" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div class="admin-link-title">Einladungen</div>
                <div class="admin-link-desc">Neue Nutzer per Link einladen</div>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Systemübersicht</div>
        </div>
        <div class="card-body" style="color:var(--p-muted);font-size:13.5px">
            Hier verwaltest du Nutzer, Klassen und Ankündigungen.
        </div>
    </div>

</div>
</x-app-layout>
