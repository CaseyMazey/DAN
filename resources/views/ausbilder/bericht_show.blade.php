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

.page{max-width:900px;margin:0 auto;padding:28px 20px 48px;display:flex;flex-direction:column;gap:16px}
.page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:4px}
.page-title{font-size:21px;font-weight:700;letter-spacing:-.4px}
.page-sub{font-size:13px;color:var(--p-muted);margin-top:3px}
.back-link{font-size:13px;color:var(--p-muted);text-decoration:none;display:flex;align-items:center;gap:4px;transition:color .15s;white-space:nowrap}
.back-link:hover{color:var(--p-text)}

.flash{padding:12px 16px;border-radius:var(--p-rsm);font-size:13.5px;border:1px solid}
.flash.ok{background:var(--p-green-bg);border-color:var(--p-green);color:var(--p-green)}
.flash.err{background:var(--p-red-bg);border-color:var(--p-red);color:var(--p-red)}

.card{background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--p-border)}
.card-title{font-size:14px;font-weight:600;color:var(--p-text);display:flex;align-items:center;gap:7px}
.card-body{padding:20px}

/* Status bar */
.status-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pill{display:inline-flex;align-items:center;gap:4px;padding:4px 11px;border-radius:99px;font-size:12px;font-weight:500}
.pill-green{background:var(--p-green-bg);color:var(--p-green)}
.pill-amber{background:var(--p-amber-bg);color:var(--p-amber)}
.pill-blue{background:var(--p-blue-bg);color:var(--p-blue)}
.pill-gray{background:var(--p-border);color:var(--p-muted)}
.pill-orange{background:var(--p-orange-bg);color:var(--p-orange)}

/* Einträge */
.sektion-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--p-muted);margin-bottom:10px}
.eintrag-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:9px 12px;background:var(--p-bg);border-radius:var(--p-rsm);margin-bottom:6px;font-size:13.5px}
.eintrag-row:last-child{margin-bottom:0}
.eintrag-desc{color:var(--p-text);flex:1}
.eintrag-std{color:var(--p-muted);font-weight:500;font-size:12.5px;white-space:nowrap}
.no-entries{font-size:13px;color:var(--p-subtle);padding:6px 0}

/* Kommentare & Bearbeitungen */
.comment-item{padding:12px 14px;border-radius:var(--p-rsm);border:1px solid var(--p-border);margin-bottom:8px}
.comment-item:last-child{margin-bottom:0}
.comment-author{font-size:13px;font-weight:600;color:var(--p-text)}
.comment-text{font-size:13.5px;color:var(--p-muted);margin-top:4px;line-height:1.5}
.comment-time{font-size:11.5px;color:var(--p-subtle);margin-top:6px}

.bearbeitung-item{padding:12px 14px;border-radius:var(--p-rsm);background:var(--p-orange-bg);border:1px solid var(--p-orange);margin-bottom:8px}
.bearbeitung-item:last-child{margin-bottom:0}
.bearbeitung-author{font-size:13px;font-weight:600;color:var(--p-orange)}
.bearbeitung-text{font-size:13.5px;color:var(--p-text);margin-top:4px;line-height:1.5}
.bearbeitung-time{font-size:11.5px;color:var(--p-orange);opacity:.7;margin-top:6px}

/* Form elements */
.fi{background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:9px 12px;font-size:13.5px;color:var(--p-text);font-family:var(--p-font);outline:none;transition:border-color .12s;width:100%;resize:vertical}
.fi:focus{border-color:var(--p-blue)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border-radius:var(--p-rsm);font-size:13.5px;font-weight:500;font-family:var(--p-font);cursor:pointer;border:none;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-gray{background:var(--p-border);color:var(--p-text)}
.btn-green{background:var(--p-green);color:#fff}

/* Signieren CTA */
.sign-cta{background:var(--p-green-bg);border:1px solid var(--p-green);border-radius:var(--p-radius);padding:20px}
.sign-cta-title{font-size:15px;font-weight:600;color:var(--p-green);margin-bottom:4px}
.sign-cta-sub{font-size:13px;color:var(--p-muted);margin-bottom:16px}
</style>

<div class="page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div class="page-title">
                Wochenbericht KW {{ $bericht->startdatum->format('W') }}
                <span style="font-weight:400;color:var(--p-muted)">/ {{ $bericht->startdatum->format('Y') }}</span>
            </div>
            <div class="page-sub">{{ $bericht->azubi->full_name }} · {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <a href="{{ route('ausbilder.bericht.pdf', $bericht->id) }}"
               class="btn btn-gray" style="font-size:12.5px;padding:6px 14px;text-decoration:none">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                PDF exportieren
            </a>
            <a href="{{ route('ausbilder.azubi.profil', $bericht->azubi_id) }}" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Alle Berichte
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flash ok">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash err">✕ {{ session('error') }}</div>
    @endif

    {{-- Status --}}
    <div class="card">
        <div class="card-body" style="padding:16px 20px">
            <div class="status-bar">
                @if($bericht->gesperrt)
                    <span class="pill pill-gray">🔒 Gesperrt</span>
                @elseif($bericht->signiert)
                    <span class="pill pill-green">✓ Von dir signiert</span>
                @elseif($bericht->azubi_signiert)
                    <span class="pill pill-amber">⏳ Wartet auf deine Signatur</span>
                @elseif($bericht->abgegeben)
                    <span class="pill pill-blue">📤 Abgegeben</span>
                @else
                    <span class="pill pill-gray">✏ In Bearbeitung</span>
                @endif
                @if($bericht->bearbeitet)
                    <span class="pill pill-orange">⚠ Nachträglich bearbeitet</span>
                @endif
                @if($bericht->azubi_signiert)
                    <span style="font-size:12px;color:var(--p-subtle)">Azubi signiert {{ $bericht->azubi_signiert_am?->format('d.m.Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- IHK-Vorlage Style --}}
    <div class="card">
        {{-- Kopfdaten --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--p-border)">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <tr>
                    <td style="padding:6px 10px;border:1px solid var(--p-border);color:var(--p-muted);width:220px">Name des/der Auszubildenden:</td>
                    <td style="padding:6px 10px;border:1px solid var(--p-border);font-weight:500">{{ $bericht->azubi->full_name }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 10px;border:1px solid var(--p-border);color:var(--p-muted)">Ausbildungswoche vom:</td>
                    <td style="padding:6px 10px;border:1px solid var(--p-border)">
                        {{ $bericht->startdatum->format('d.m.Y') }}
                        <span style="color:var(--p-muted);margin:0 8px">bis</span>
                        {{ $bericht->enddatum->format('d.m.Y') }}
                        <span style="color:var(--p-muted);margin-left:12px">KW {{ $bericht->startdatum->format('W / Y') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Sektionen als IHK-Tabellen --}}
        @foreach([
            'betrieb'      => 'Betriebliche Tätigkeiten',
            'unterweisung' => 'Unterweisungen bzw. überbetriebliche Unterweisungen, betrieblicher Unterricht, sonstige Schulungen',
            'berufsschule' => 'Themen des Berufsschulunterrichts',
        ] as $key => $label)
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr>
                    <th style="padding:8px 14px;background:#E8EDF4;border:1px solid var(--p-border);text-align:left;font-weight:600;font-size:12.5px;color:var(--p-text)">
                        {{ $label }}
                    </th>
                    <th style="padding:8px 14px;background:#E8EDF4;border:1px solid var(--p-border);text-align:right;font-style:italic;font-weight:400;font-size:12.5px;color:var(--p-muted);width:90px">
                        Stunden
                    </th>
                </tr>
            </thead>
            <tbody>
                @if(isset($eintraege[$key]) && $eintraege[$key]->count())
                    @foreach($eintraege[$key] as $eintrag)
                        <tr>
                            <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none;font-size:13px">{{ $eintrag->beschreibung }}</td>
                            <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;font-size:13px;color:var(--p-muted)">{{ number_format($eintrag->stunden, 2) }}</td>
                        </tr>
                    @endforeach
                    {{-- Summenzeile --}}
                    <tr>
                        <td style="padding:6px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;font-size:12px;color:var(--p-muted)">Gesamt:</td>
                        <td style="padding:6px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;font-weight:600;font-size:13px">{{ number_format($eintraege[$key]->sum('stunden'), 2) }}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" style="padding:16px 14px;border:1px solid var(--p-border);border-top:none;color:var(--p-subtle);font-size:13px;height:60px;vertical-align:top"> </td>
                    </tr>
                @endif
            </tbody>
        </table>
        @endforeach

        {{-- Unterschriften --}}
        <div style="padding:20px 24px;border-top:1px solid var(--p-border)">
            <p style="font-size:12.5px;color:var(--p-muted);margin-bottom:20px">
                Durch die nachfolgende Unterschrift wird die Richtigkeit und Vollständigkeit der obigen Angaben bestätigt.
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px">
                {{-- Azubi Signatur --}}
                <div>
                    <div style="height:48px;display:flex;align-items:center;margin-bottom:6px">
                        @if($bericht->azubi_signiert)
                            <span class="pill pill-green">✓ Vom Azubi signiert {{ $bericht->azubi_signiert_am?->format('d.m.Y') }}</span>
                        @else
                            <div style="width:100%;border-bottom:1px solid var(--p-border)"></div>
                        @endif
                    </div>
                    <div style="font-size:12px;color:var(--p-muted)">Datum, Unterschrift Auszubildende/r</div>
                </div>
                {{-- Ausbilder Signatur --}}
                <div>
                    <div style="height:48px;display:flex;align-items:center;margin-bottom:6px">
                        @if($bericht->signiert)
                            <span class="pill pill-green">✓ Vom Ausbilder signiert {{ $bericht->signiert_am?->format('d.m.Y') }}</span>
                        @else
                            <div style="width:100%;border-bottom:1px solid var(--p-border)"></div>
                        @endif
                    </div>
                    <div style="font-size:12px;color:var(--p-muted)">Datum, Unterschrift Ausbildende/r oder Ausbilder/in</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bearbeitungshistorie --}}
    @if($bericht->bearbeitungen->count())
        <div class="card">
            <div class="card-header">
                <span class="card-title" style="color:var(--p-orange)">⚠ Bearbeitungshistorie</span>
                <span style="font-size:12.5px;color:var(--p-muted)">{{ $bericht->bearbeitungen->count() }} Einträge</span>
            </div>
            <div class="card-body">
                @foreach($bericht->bearbeitungen as $b)
                    <div class="bearbeitung-item">
                        <div class="bearbeitung-author">{{ $b->autor->full_name }}</div>
                        <div class="bearbeitung-text">{{ $b->kommentar }}</div>
                        <div class="bearbeitung-time">{{ $b->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Kommentare --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">💬 Kommentare</span>
            @if($bericht->kommentare->count())
                <span style="font-size:12.5px;color:var(--p-muted)">{{ $bericht->kommentare->count() }}</span>
            @endif
        </div>
        <div class="card-body">
            @if($bericht->kommentare->count())
                <div style="margin-bottom:16px">
                    @foreach($bericht->kommentare as $k)
                        <div class="comment-item">
                            <div class="comment-author">{{ $k->autor->full_name }}</div>
                            <div class="comment-text">{{ $k->inhalt }}</div>
                            <div class="comment-time">{{ $k->created_at->format('d.m.Y H:i') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if(!$bericht->gesperrt)
                <form method="POST" action="{{ route('ausbilder.bericht.kommentieren', $bericht->id) }}">
                    @csrf
                    <textarea name="inhalt" rows="3" class="fi" required placeholder="Kommentar schreiben..." style="margin-bottom:10px"></textarea>
                    <button type="submit" class="btn btn-gray">💬 Kommentar senden</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Signieren --}}
    @if(!$bericht->signiert && !$bericht->gesperrt && $bericht->azubi_signiert)
        <div class="sign-cta">
            <div class="sign-cta-title">✍️ Bericht signieren</div>
            <div class="sign-cta-sub">Mit deiner Signatur wird der Bericht gesperrt und kann nicht mehr bearbeitet werden.</div>
            <form method="POST" action="{{ route('ausbilder.bericht.signieren', $bericht->id) }}"
                onsubmit="return confirm('Bericht wirklich signieren und sperren?')">
                @csrf
                <button type="submit" class="btn btn-green">✍️ Jetzt signieren & sperren</button>
            </form>
        </div>
    @endif

</div>
</x-app-layout>
