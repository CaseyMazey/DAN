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

.show-wrap{max-width:820px;margin:0 auto;padding:28px 20px 60px}

/* Topbar */
.show-topbar{
    display:flex;align-items:flex-start;justify-content:space-between;
    gap:12px;margin-bottom:20px;flex-wrap:wrap
}
.show-heading{font-size:21px;font-weight:700;letter-spacing:-.4px;line-height:1.2}
.show-subheading{font-size:13px;color:var(--p-muted);margin-top:3px}

/* Reusable */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--p-rsm);font-size:13px;font-weight:500;text-decoration:none;cursor:pointer;border:none;font-family:var(--p-font);transition:all .12s;line-height:1}
.btn-primary{background:var(--p-blue);color:#fff}.btn-primary:hover{opacity:.88}
.btn-ghost{background:var(--p-surface);border:1px solid var(--p-border);color:var(--p-muted)}.btn-ghost:hover{border-color:var(--p-blue);color:var(--p-blue)}
.btn-amber{background:var(--p-amber-bg);color:var(--p-amber);border:1px solid transparent}.btn-amber:hover{border-color:var(--p-amber)}
.btn-green{background:var(--p-green-bg);color:var(--p-green);border:1px solid transparent}.btn-green:hover{border-color:var(--p-green)}

.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:11.5px;font-weight:500;white-space:nowrap}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}.pill-orange{background:var(--p-orange-bg);color:var(--p-orange)}.pill-gray{background:var(--p-gray-bg);color:var(--p-muted)}.pill-dark{background:var(--p-border);color:var(--p-subtle)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden;margin-bottom:16px}
.card:last-child{margin-bottom:0}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:15px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.card-body{padding:20px}

.flash{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid;margin-bottom:16px}
.flash-ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash-err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

/* Status + action row */
.status-action{
    display:flex;align-items:center;justify-content:space-between;
    gap:12px;flex-wrap:wrap;
    padding:14px 20px;
    border-bottom:1px solid var(--p-border);
    background:var(--p-gray-bg)
}
.status-pills{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.action-btns{display:flex;flex-wrap:wrap;gap:6px;align-items:center}

/* Einträge */
.kategorie-label{
    display:flex;align-items:center;justify-content:space-between;
    font-size:11px;font-weight:600;text-transform:uppercase;
    letter-spacing:.6px;color:var(--p-subtle);
    margin-bottom:8px;padding-bottom:6px;
    border-bottom:1px solid var(--p-border)
}
.eintrag-item{
    display:flex;align-items:flex-start;justify-content:space-between;
    gap:10px;padding:9px 12px;
    background:var(--p-gray-bg);border-radius:var(--p-rsm);
    font-size:13.5px;margin-bottom:6px;
    border:1px solid transparent
}
.eintrag-item:last-of-type{margin-bottom:0}
.eintrag-hrs{color:var(--p-muted);font-size:12.5px;white-space:nowrap;flex-shrink:0;padding-top:1px}
.kategorie-total{font-size:12px;font-weight:600;color:var(--p-muted);text-align:right;margin-top:8px}
.kategorie-empty{font-size:13px;color:var(--p-subtle);padding:6px 0}

/* Eintrag-Form */
.entry-form{
    margin-top:16px;padding-top:16px;
    border-top:1px solid var(--p-border)
}
.form-row{display:flex;gap:10px;margin-bottom:10px}
.form-row textarea,.form-row input{
    background:var(--p-bg);border:1px solid var(--p-border);
    border-radius:var(--p-rsm);padding:8px 12px;
    font-size:13px;color:var(--p-text);font-family:var(--p-font);
    outline:none;transition:border-color .12s;resize:vertical
}
.form-row textarea{flex:1;min-height:60px}
.form-row textarea:focus,.form-row input:focus{border-color:var(--p-blue)}
.form-row input[type=number]{width:72px;flex-shrink:0}
.bearbeitungs-notice{
    display:flex;align-items:flex-start;gap:8px;
    background:var(--p-orange-bg);border:1px solid var(--p-orange);
    border-radius:var(--p-rsm);padding:10px 12px;
    font-size:12.5px;color:var(--p-orange);margin-bottom:10px
}
.bearbeitungs-notice textarea{
    background:var(--p-surface);border:1px solid var(--p-orange);
    border-radius:var(--p-rsm);padding:8px 12px;
    font-size:13px;color:var(--p-text);font-family:var(--p-font);
    outline:none;transition:border-color .12s;resize:vertical;
    width:100%;margin-top:6px
}

/* Kommentare / Bearbeitungen */
.comment-item{
    padding:12px 14px;
    background:var(--p-blue-bg);border:1px solid var(--p-blue-light);
    border-radius:var(--p-rsm);margin-bottom:8px
}
.comment-item:last-child{margin-bottom:0}
.comment-meta{font-size:11.5px;color:var(--p-muted);margin-bottom:5px}
.comment-text{font-size:13.5px}

.history-item{
    padding:12px 14px;
    background:var(--p-orange-bg);border:1px solid transparent;
    border-radius:var(--p-rsm);margin-bottom:8px
}
.history-item:last-child{margin-bottom:0}
.history-meta{font-size:11.5px;color:var(--p-muted);margin-bottom:5px}
.history-text{font-size:13.5px;color:var(--p-text)}

/* Gesamtstunden footer */
.total-bar{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;
    background:var(--p-gray-bg);border-top:1px solid var(--p-border)
}
.total-label{font-size:13px;color:var(--p-muted)}
.total-value{font-size:20px;font-weight:700;color:var(--p-text)}
</style>

<div class="show-wrap">

    {{-- Topbar --}}
    <div class="show-topbar">
        <div>
            <div class="show-heading">
                Wochenbericht KW {{ $bericht->startdatum->format('W') }} / {{ $bericht->startdatum->format('Y') }}
            </div>
            <div class="show-subheading">
                {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
            </div>
        </div>
        <a href="{{ route('wochenbericht.index') }}" class="btn btn-ghost">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
            Alle Berichte
        </a>
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

    {{-- Status & Aktionen --}}
    <div class="card">
        <div class="status-action">
            <div class="status-pills">
                @if($bericht->gesperrt)
                    <span class="pill pill-dark">🔒 Gesperrt</span>
                @elseif($bericht->signiert)
                    <span class="pill pill-green">✓ Ausbilder signiert</span>
                @elseif($bericht->azubi_signiert)
                    <span class="pill pill-blue">✍ Von dir signiert</span>
                @elseif($bericht->abgegeben)
                    <span class="pill pill-amber">↑ Abgegeben</span>
                @else
                    <span class="pill pill-gray">✏ In Bearbeitung</span>
                @endif
                @if($bericht->bearbeitet)
                    <span class="pill pill-orange">⚠ Nachträglich bearbeitet</span>
                @endif
            </div>
            @if(!$bericht->gesperrt)
                <div class="action-btns">
                    @if(!$bericht->abgegeben)
                        <form method="POST" action="{{ route('wochenbericht.abgeben', $bericht->id) }}">
                            @csrf
                            <button class="btn btn-amber">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                Abgeben
                            </button>
                        </form>
                    @endif
                    @if($bericht->abgegeben && !$bericht->azubi_signiert)
                        <form method="POST" action="{{ route('wochenbericht.signieren', $bericht->id) }}">
                            @csrf
                            <button class="btn btn-green">✍ Selbst signieren</button>
                        </form>
                    @endif
                    <a href="{{ route('wochenbericht.pdf', $bericht->id) }}" class="btn btn-ghost">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        PDF
                    </a>
                </div>
            @else
                <a href="{{ route('wochenbericht.pdf', $bericht->id) }}" class="btn btn-ghost">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    PDF exportieren
                </a>
            @endif
        </div>

        {{-- ── Drei Kategorien ── --}}
        @foreach([
            'betrieb'      => ['label' => 'Betriebliche Tätigkeiten', 'icon' => '<path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>'],
            'unterweisung' => ['label' => 'Unterweisungen / Schulungen', 'icon' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>'],
            'berufsschule' => ['label' => 'Themen des Berufsschulunterrichts', 'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
        ] as $key => $info)
            @php
                $kat = $eintraege[$key] ?? collect();
                $summe = $kat->sum('stunden');
            @endphp
            <div class="card-body" style="border-top:1px solid var(--p-border)">
                <div class="kategorie-label">
                    <span style="display:flex;align-items:center;gap:7px">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">{!! $info['icon'] !!}</svg>
                        {{ $info['label'] }}
                    </span>
                    @if($summe > 0)
                        <span style="font-size:12px;font-weight:600;color:var(--p-muted)">{{ $summe }} Std gesamt</span>
                    @endif
                </div>

                @if($kat->count())
                    @foreach($kat as $eintrag)
                        <div class="eintrag-item">
                            <span>{{ $eintrag->beschreibung }}</span>
                            <span class="eintrag-hrs">{{ $eintrag->stunden }} Std</span>
                        </div>
                    @endforeach
                @else
                    <div class="kategorie-empty">Noch keine Einträge.</div>
                @endif

                {{-- Neuen Eintrag --}}
                @if(!$bericht->gesperrt)
                    <div class="entry-form">
                        @if($bericht->abgegeben)
                            <div class="bearbeitungs-notice">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                <div style="flex:1">
                                    Nachträgliche Bearbeitung — Pflichtkommentar erforderlich.
                                </div>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('wochenbericht.speichern', $bericht->id) }}">
                            @csrf
                            <input type="hidden" name="kategorie" value="{{ $key }}">
                            <div class="form-row">
                                <textarea name="beschreibung" placeholder="Was wurde gemacht?" required></textarea>
                                <input type="number" name="stunden" step="0.5" min="0" max="99" placeholder="Std" required>
                            </div>
                            @if($bericht->abgegeben)
                                <textarea name="bearbeitungs_kommentar"
                                    placeholder="Was wurde nachträglich geändert / ergänzt?"
                                    style="width:100%;background:var(--p-bg);border:1px solid var(--p-orange);border-radius:var(--p-rsm);padding:8px 12px;font-size:13px;color:var(--p-text);font-family:var(--p-font);outline:none;resize:vertical;min-height:56px;margin-bottom:10px"
                                    required></textarea>
                            @endif
                            <button type="submit" class="btn btn-primary" style="font-size:12.5px;padding:7px 13px">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Eintrag hinzufügen
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Gesamtstunden --}}
        <div class="total-bar">
            <span class="total-label">Gesamtstunden diese Woche</span>
            <span class="total-value">{{ $bericht->gesamtstunden() }} Std</span>
        </div>
    </div>

    {{-- Kommentare --}}
    @if($kommentare->count())
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Kommentare
                </span>
                <span class="pill pill-blue">{{ $kommentare->count() }}</span>
            </div>
            <div class="card-body">
                @foreach($kommentare as $k)
                    <div class="comment-item">
                        <div class="comment-meta">{{ $k->autor->full_name }} · {{ $k->created_at->format('d.m.Y H:i') }}</div>
                        <div class="comment-text">{{ $k->inhalt }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Bearbeitungshistorie --}}
    @if($bearbeitungen->count())
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg>
                    Bearbeitungshistorie
                </span>
                <span class="pill pill-orange">{{ $bearbeitungen->count() }}</span>
            </div>
            <div class="card-body">
                @foreach($bearbeitungen as $b)
                    <div class="history-item">
                        <div class="history-meta">{{ $b->created_at->format('d.m.Y H:i') }}</div>
                        <div class="history-text">{{ $b->kommentar }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
</x-app-layout>
