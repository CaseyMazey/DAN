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
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--p-bg); color: var(--p-text); font-family: var(--p-font); }

.page { max-width: 860px; margin: 0 auto; padding: 36px 20px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; gap: 12px; flex-wrap: wrap; }
.page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }

.flash { padding: 12px 16px; border-radius: var(--p-rsm); font-size: 13.5px; margin-bottom: 20px; }
.flash.success { background: var(--p-green-bg); color: var(--p-green); }
.flash.error   { background: var(--p-red-bg);   color: var(--p-red); }

.card { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); padding: 24px; margin-bottom: 16px; }
.card-title { font-size: 15px; font-weight: 600; margin-bottom: 18px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media(max-width:600px){ .form-grid { grid-template-columns: 1fr; } }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group.span2 { grid-column: span 2; }
@media(max-width:600px){ .form-group.span2 { grid-column: span 1; } }
label { font-size: 12.5px; font-weight: 500; color: var(--p-muted); }
input, textarea {
    width: 100%; padding: 8px 12px; border-radius: var(--p-rsm);
    border: 1px solid var(--p-border); background: var(--p-bg);
    color: var(--p-text); font-family: var(--p-font); font-size: 13.5px;
    transition: border-color .15s;
}
input:focus, textarea:focus { outline: none; border-color: var(--p-blue); }
textarea { resize: vertical; min-height: 64px; }
.error-msg { font-size: 11.5px; color: var(--p-red); }

.btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: var(--p-rsm); font-size: 13px; font-weight: 500; cursor: pointer; border: none; font-family: var(--p-font); transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-primary  { background: var(--p-blue); color: #fff; }
.btn-secondary{ background: var(--p-border); color: var(--p-text); }
.btn-danger   { background: var(--p-red-bg); color: var(--p-red); }
.btn-sm       { padding: 5px 11px; font-size: 12.5px; }

.abt-item { background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius); padding: 18px 20px; margin-bottom: 12px; }
.abt-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.abt-meta { flex: 1; min-width: 0; }
.abt-name { font-size: 16px; font-weight: 600; }
.abt-badges { display: flex; align-items: center; gap: 6px; margin-top: 4px; flex-wrap: wrap; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 11.5px; font-weight: 500; }
.badge-mono  { background: var(--p-bg); color: var(--p-muted); border: 1px solid var(--p-border); font-family: monospace; }
.badge-inaktiv { background: var(--p-red-bg); color: var(--p-red); }
.abt-desc { font-size: 13px; color: var(--p-muted); margin-top: 6px; }
.abt-count { font-size: 12.5px; color: var(--p-subtle); margin-top: 4px; }
.abt-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: flex-start; }

.abt-edit { display: none; margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--p-border); }
.abt-edit.open { display: block; }

.empty { text-align: center; padding: 48px 20px; color: var(--p-muted); }
.empty p { font-size: 14px; }
.empty span { font-size: 13px; color: var(--p-subtle); display: block; margin-top: 4px; }
</style>

<div class="page">
    <div class="page-header">
        <h1 class="page-title">Abteilungen</h1>
    </div>

    @if(session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    {{-- Neue Abteilung --}}
    <div class="card">
        <div class="card-title">Neue Abteilung erstellen</div>
        <form method="POST" action="{{ route('admin.abteilungen.erstellen') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="z.B. IT Anwendungsentwicklung" required>
                    @error('name')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>Kürzel</label>
                    <input type="text" name="kuerzel" value="{{ old('kuerzel') }}" placeholder="z.B. IT-AE" maxlength="20">
                    @error('kuerzel')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="form-group span2">
                    <label>Beschreibung</label>
                    <textarea name="beschreibung" placeholder="Optionale Beschreibung">{{ old('beschreibung') }}</textarea>
                </div>
            </div>
            <div style="margin-top:16px">
                <button type="submit" class="btn btn-primary">Abteilung erstellen</button>
            </div>
        </form>
    </div>

    {{-- Liste --}}
    @forelse($abteilungen as $abteilung)
        <div class="abt-item">
            <div class="abt-header">
                <div class="abt-meta">
                    <div class="abt-name">{{ $abteilung->name }}</div>
                    <div class="abt-badges">
                        @if($abteilung->kuerzel)
                            <span class="badge badge-mono">{{ $abteilung->kuerzel }}</span>
                        @endif
                        @if(!$abteilung->aktiv)
                            <span class="badge badge-inaktiv">Inaktiv</span>
                        @endif
                    </div>
                    @if($abteilung->beschreibung)
                        <div class="abt-desc">{{ $abteilung->beschreibung }}</div>
                    @endif
                    <div class="abt-count">
                        {{ $abteilung->klassen_count }} {{ $abteilung->klassen_count === 1 ? 'Klasse' : 'Klassen' }}
                    </div>
                </div>
                <div class="abt-actions">
                    <button class="btn btn-secondary btn-sm" onclick="toggleEdit({{ $abteilung->id }})">Bearbeiten</button>
                    @if($abteilung->klassen_count === 0)
                        <form method="POST" action="{{ route('admin.abteilungen.loeschen', $abteilung->id) }}"
                              onsubmit="return confirm('Abteilung wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="abt-edit" id="edit-{{ $abteilung->id }}">
                <form method="POST" action="{{ route('admin.abteilungen.update', $abteilung->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" value="{{ $abteilung->name }}" required>
                        </div>
                        <div class="form-group">
                            <label>Kürzel</label>
                            <input type="text" name="kuerzel" value="{{ $abteilung->kuerzel }}" maxlength="20">
                        </div>
                        <div class="form-group span2">
                            <label>Beschreibung</label>
                            <textarea name="beschreibung">{{ $abteilung->beschreibung }}</textarea>
                        </div>
                        <div class="form-group" style="flex-direction:row;align-items:center;gap:8px">
                            <input type="hidden" name="aktiv" value="0">
                            <input type="checkbox" name="aktiv" id="aktiv-{{ $abteilung->id }}" value="1"
                                   {{ $abteilung->aktiv ? 'checked' : '' }} style="width:auto">
                            <label for="aktiv-{{ $abteilung->id }}" style="margin:0;cursor:pointer">Aktiv</label>
                        </div>
                    </div>
                    <div style="margin-top:14px;display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary btn-sm">Speichern</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit({{ $abteilung->id }})">Abbrechen</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="empty">
            <p>Noch keine Abteilungen angelegt.</p>
            <span>Erstelle oben deine erste Abteilung.</span>
        </div>
    @endforelse
</div>

<script>
function toggleEdit(id) {
    document.getElementById('edit-' + id).classList.toggle('open');
}
</script>

</x-app-layout>
