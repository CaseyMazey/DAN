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
    --p-orange:#C2410C;--p-orange-bg:#FFF7ED;
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
    --p-orange:#F97316;--p-orange-bg:#2A1500;
    --p-gray-bg:#21262D;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--p-font);background:var(--p-bg);color:var(--p-text)}

.wb-wrap{max-width:1100px;margin:0 auto;padding:28px 20px 60px}
.wb-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px}
.wb-title{font-size:22px;font-weight:700;letter-spacing:-.4px}
.wb-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start}
@media(max-width:860px){.wb-grid{grid-template-columns:1fr}}

.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--p-rsm);font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:none;font-family:var(--p-font);transition:all .12s;line-height:1}
.btn-primary{background:var(--p-blue);color:#fff}.btn-primary:hover{opacity:.88}
.btn-ghost{background:var(--p-surface);border:1px solid var(--p-border);color:var(--p-muted)}.btn-ghost:hover{border-color:var(--p-blue);color:var(--p-blue)}
.btn-green{background:var(--p-green-bg);color:var(--p-green);border:1px solid transparent}.btn-green:hover{border-color:var(--p-green)}
.btn-danger{background:var(--p-red-bg);color:var(--p-red);border:1px solid transparent}.btn-danger:hover{border-color:var(--p-red)}

.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500;white-space:nowrap}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}.pill-orange{background:var(--p-orange-bg);color:var(--p-orange)}.pill-gray{background:var(--p-gray-bg);color:var(--p-muted)}.pill-dark{background:var(--p-border);color:var(--p-subtle)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:20px}

.flash{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid;margin-bottom:16px}
.flash-ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash-err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.status-bar{display:flex;flex-wrap:wrap;gap:6px;padding:14px 20px 0}
.eintrag-section{margin-bottom:18px}.eintrag-section:last-child{margin-bottom:0}
.eintrag-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--p-subtle);margin-bottom:6px}
.eintrag-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;padding:8px 10px;background:var(--p-gray-bg);border-radius:var(--p-rsm);font-size:13px;margin-bottom:4px;border:1px solid transparent}
.eintrag-row.pending{background:var(--p-blue-bg);border-color:var(--p-blue-light)}
.eintrag-pending-name{color:var(--p-blue);font-weight:500}
.eintrag-hrs{color:var(--p-muted);font-size:12px;white-space:nowrap;flex-shrink:0}
.eintrag-empty{font-size:12.5px;color:var(--p-subtle)}
.action-bar{display:flex;flex-wrap:wrap;gap:8px;padding:14px 20px;border-top:1px solid var(--p-border);background:var(--p-gray-bg)}

.filter-bar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--p-border)}
.filter-bar select,.filter-bar input[type=number]{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:6px 10px;font-size:12.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--p-blue)}
.filter-bar input[type=number]{width:62px}
.filter-cb{display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--p-muted);cursor:pointer}
.filter-cb input{accent-color:var(--p-blue)}
.sammel-bar{display:flex;gap:6px;padding:10px 20px;border-bottom:1px solid var(--p-border)}

.bericht-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:11px 20px;border-bottom:1px solid var(--p-border);transition:background .1s}
.bericht-row:last-child{border-bottom:none}.bericht-row:hover{background:var(--p-gray-bg)}.bericht-row.active{background:var(--p-blue-bg)}
.bericht-kw{font-size:13px;font-weight:600}.bericht-date{font-size:11.5px;color:var(--p-muted);margin-top:1px}
.sig-dot{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0}
.sig-dot.on{background:var(--p-green-bg);color:var(--p-green)}.sig-dot.off{background:var(--p-border);color:var(--p-subtle)}
</style>

<div class="wb-wrap">

    <div class="wb-topbar">
        <div class="wb-title">Wochenberichte</div>
        <form method="POST" action="{{ route('wochenbericht.fehlend') }}">
            @csrf
            <button class="btn btn-ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Fehlende Berichte erstellen
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="flash flash-ok">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-err">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="wb-grid">

        {{-- ── LEFT: Vorschau / aktueller Bericht ── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    @if($vorschau && $aktuell && $vorschau->id === $aktuell->id)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Aktuelle Woche
                    @elseif($vorschau)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Vorschau — KW {{ $vorschau->startdatum->format('W / Y') }}
                    @else
                        Aktuelle Woche
                    @endif
                </span>
                @if($vorschau)
                    <span style="font-size:12px;color:var(--p-muted)">
                        {{ $vorschau->startdatum->format('d.m') }} – {{ $vorschau->enddatum->format('d.m.Y') }}
                    </span>
                @endif
            </div>

            @if($vorschau)
                <div class="status-bar">
                    @if($vorschau->gesperrt)
                        <span class="pill pill-dark">🔒 Gesperrt</span>
                    @elseif($vorschau->signiert)
                        <span class="pill pill-green">✓ Ausbilder signiert</span>
                    @elseif($vorschau->azubi_signiert)
                        <span class="pill pill-blue">✍ Von dir signiert</span>
                    @elseif($vorschau->abgegeben)
                        <span class="pill pill-amber">↑ Abgegeben</span>
                    @else
                        <span class="pill pill-gray">✏ In Bearbeitung</span>
                    @endif
                    @if($vorschau->bearbeitet)
                        <span class="pill pill-orange">⚠ Nachträglich bearbeitet</span>
                    @endif
                </div>

                <div class="card-body" style="padding-top:16px">
                    @foreach(['betrieb' => 'Betriebliche Tätigkeiten', 'unterweisung' => 'Unterweisungen', 'berufsschule' => 'Berufsschule'] as $key => $label)
                        @php
                            $eintraege = $vorschau->eintraege->where('kategorie', $key);
                            $aufgaben  = $aufgabenVorschau->get($key, collect());
                        @endphp
                        <div class="eintrag-section">
                            <div class="eintrag-label">{{ $label }}</div>
                            @foreach($eintraege as $e)
                                <div class="eintrag-row">
                                    <span>{{ $e->beschreibung }}</span>
                                    <span class="eintrag-hrs">{{ $e->stunden }} Std</span>
                                </div>
                            @endforeach
                            @foreach($aufgaben as $a)
                                <div class="eintrag-row pending">
                                    <div>
                                        <div class="eintrag-pending-name">
                                            {{ $a->titel }}
                                            <span style="font-size:11px;font-weight:400;opacity:.7;margin-left:3px">({{ $a->status === 'fertig' ? 'Fertig' : 'In Bearbeitung' }})</span>
                                        </div>
                                        @if($a->beschreibung)
                                            <div style="font-size:12px;color:var(--p-blue);opacity:.8;margin-top:2px">{{ $a->beschreibung }}</div>
                                        @endif
                                    </div>
                                    <span class="eintrag-hrs">{{ $a->stunden }} Std</span>
                                </div>
                            @endforeach
                            @if($eintraege->count() === 0 && $aufgaben->count() === 0)
                                <div class="eintrag-empty">Keine Einträge.</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="action-bar">
                    <a href="{{ route('wochenbericht.show', $vorschau->id) }}" class="btn btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Bearbeiten
                    </a>
                    @if(!$vorschau->azubi_signiert)
                        <form method="POST" action="{{ route('wochenbericht.signieren', $vorschau->id) }}">
                            @csrf
                            <button class="btn btn-green">✍ Signieren</button>
                        </form>
                    @else
                        <span class="pill pill-green" style="padding:7px 12px">✓ Signiert</span>
                        <form method="POST" action="{{ route('wochenbericht.signatur.loeschen', $vorschau->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger">Signatur löschen</button>
                        </form>
                    @endif
                    <a href="{{ route('wochenbericht.pdf', $vorschau->id) }}" class="btn btn-ghost">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        PDF
                    </a>
                    @if($aktuell && $vorschau->id !== $aktuell->id)
                        <a href="{{ route('wochenbericht.index') }}" class="btn btn-ghost">↩ Zurück</a>
                    @endif
                </div>
            @else
                <div class="card-body">
                    <p style="font-size:13px;color:var(--p-muted)">Kein Bericht für diese Woche vorhanden.</p>
                </div>
            @endif
        </div>

        {{-- ── RIGHT: Vergangene Berichte ── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg>
                    Vergangene Berichte
                </span>
                <span style="font-size:11.5px;color:var(--p-subtle)">max. 10</span>
            </div>

            <form method="GET" action="{{ route('wochenbericht.index') }}">
                <div class="filter-bar">
                    <select name="jahr">
                        <option value="">Alle Jahre</option>
                        @foreach($jahre as $jahr)
                            <option value="{{ $jahr }}" {{ request('jahr') == $jahr ? 'selected' : '' }}>{{ $jahr }}</option>
                        @endforeach
                    </select>
                    <select name="monat">
                        <option value="">Monat</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ request('monat') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('de')->monthName }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" name="kw" placeholder="KW" value="{{ request('kw') }}">
                    <label class="filter-cb">
                        <input type="checkbox" name="nur_signierte" value="1" {{ request('nur_signierte') ? 'checked' : '' }}>
                        Nur signierte
                    </label>
                    <button type="submit" class="btn btn-primary" style="padding:6px 12px;font-size:12px">Filtern</button>
                    <a href="{{ route('wochenbericht.index') }}" class="btn btn-ghost" style="padding:6px 10px;font-size:12px">✕</a>
                </div>
            </form>

            <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-sammel">
                @csrf
                <input type="hidden" name="ids" id="sammel-ids">
                <div class="sammel-bar">
                    <button type="button" onclick="alleAuswaehlen()" class="btn btn-ghost" style="padding:5px 10px;font-size:12px">Alle wählen</button>
                    <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:12px">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Sammel-PDF
                    </button>
                </div>

                @forelse($vergangen as $bericht)
                    <div class="bericht-row {{ $vorschau && $vorschau->id === $bericht->id ? 'active' : '' }}">
                        <div style="display:flex;align-items:center;gap:10px">
                            <input type="checkbox" class="sammel-check" value="{{ $bericht->id }}"
                                   style="accent-color:var(--p-blue);flex-shrink:0">
                            <div>
                                <div class="bericht-kw">KW {{ $bericht->startdatum->format('W / Y') }}</div>
                                <div class="bericht-date">{{ $bericht->startdatum->format('d.m') }} – {{ $bericht->enddatum->format('d.m.Y') }}</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;flex-shrink:0">
                            <div style="display:flex;gap:3px">
                                <div class="sig-dot {{ $bericht->azubi_signiert ? 'on' : 'off' }}" title="Azubi">A</div>
                                <div class="sig-dot {{ $bericht->signiert ? 'on' : 'off' }}" title="Ausbilder">L</div>
                            </div>
                            @if($bericht->bearbeitet)
                                <span class="pill pill-orange" style="padding:2px 6px;font-size:10.5px">⚠</span>
                            @endif
                            <a href="{{ route('wochenbericht.index', ['vorschau' => $bericht->id]) }}"
                               class="btn {{ $vorschau && $vorschau->id === $bericht->id ? 'btn-primary' : 'btn-ghost' }}"
                               style="padding:5px 10px;font-size:12px">
                                {{ $vorschau && $vorschau->id === $bericht->id ? '● Aktiv' : 'Vorschau' }}
                            </a>
                            <a href="{{ route('wochenbericht.pdf', $bericht->id) }}"
                               class="btn btn-ghost" style="padding:5px 8px;font-size:12px">PDF</a>
                        </div>
                    </div>
                @empty
                    <div style="padding:20px;font-size:13px;color:var(--p-muted)">Keine vergangenen Berichte.</div>
                @endforelse
            </form>
        </div>

    </div>
</div>

<script>
function alleAuswaehlen() {
    document.querySelectorAll('.sammel-check').forEach(cb => cb.checked = true);
}
document.getElementById('form-sammel').addEventListener('submit', function(e) {
    const ids = [...document.querySelectorAll('.sammel-check:checked')].map(cb => cb.value);
    if (!ids.length) { e.preventDefault(); alert('Bitte mindestens einen Bericht auswählen.'); return; }
    document.getElementById('sammel-ids').value = ids.join(',');
});
</script>

</x-app-layout>
