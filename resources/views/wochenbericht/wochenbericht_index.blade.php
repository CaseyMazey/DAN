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
.wb-grid{display:flex;flex-direction:column;gap:20px}

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

        {{-- Stat-Karten --}}
        @php
            $stats = [
                'gesamt'     => \App\Models\Wochenbericht::where('azubi_id', auth()->id())->count(),
                'signiert'   => \App\Models\Wochenbericht::where('azubi_id', auth()->id())->where('signiert', true)->count(),
                'ausstehend' => \App\Models\Wochenbericht::where('azubi_id', auth()->id())->where('azubi_signiert', true)->where('signiert', false)->count(),
                'bearbeitet' => \App\Models\Wochenbericht::where('azubi_id', auth()->id())->where('bearbeitet', true)->count(),
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
            <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);padding:18px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--p-text);line-height:1">{{ $stats['gesamt'] }}</div>
                <div style="font-size:12px;color:var(--p-muted);margin-top:5px">Berichte gesamt</div>
            </div>
            <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);padding:18px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--p-green);line-height:1">{{ $stats['signiert'] }}</div>
                <div style="font-size:12px;color:var(--p-muted);margin-top:5px">Signiert</div>
            </div>
            <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);padding:18px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--p-amber);line-height:1">{{ $stats['ausstehend'] }}</div>
                <div style="font-size:12px;color:var(--p-muted);margin-top:5px">Warten auf Ausbilder</div>
            </div>
            <div style="background:var(--p-surface);border:1px solid var(--p-border);border-radius:var(--p-radius);padding:18px 20px">
                <div style="font-size:28px;font-weight:700;color:var(--p-red);line-height:1">{{ $stats['bearbeitet'] }}</div>
                <div style="font-size:12px;color:var(--p-muted);margin-top:5px">Nachträglich bearbeitet</div>
            </div>
        </div>

        {{-- ── Vorschau / aktueller Bericht ── --}}
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
                <div style="display:flex;align-items:center;gap:10px">
                    @if($vorschau)
                        <a href="{{ route('wochenbericht.pdf', $vorschau->id) }}" class="btn btn-ghost" style="font-size:12px;padding:5px 12px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            PDF exportieren
                        </a>
                        @if($aktuell && $vorschau->id !== $aktuell->id)
                            <a href="{{ route('wochenbericht.index') }}" class="btn btn-ghost" style="font-size:12px;padding:5px 12px">← Aktuelle Woche</a>
                        @endif
                    @endif
                </div>
            </div>

            @if($vorschau)
                {{-- Status --}}
                <div class="status-bar" style="padding:12px 20px;border-bottom:1px solid var(--p-border)">
                    @if($vorschau->gesperrt)
                        <span class="pill pill-dark">🔒 Gesperrt</span>
                    @elseif($vorschau->signiert)
                        <span class="pill pill-green">✓ Ausbilder signiert</span>
                    @elseif($vorschau->azubi_signiert)
                        <span class="pill pill-blue">✍ Von dir signiert</span>
                        @if($vorschau->azubi_signiert_am)
                            <span style="font-size:12px;color:var(--p-muted)">Azubi signiert {{ $vorschau->azubi_signiert_am->format('d.m.Y') }}</span>
                        @endif
                    @elseif($vorschau->abgegeben)
                        <span class="pill pill-amber">↑ Abgegeben</span>
                    @else
                        <span class="pill pill-gray">✏ In Bearbeitung</span>
                    @endif
                    @if($vorschau->bearbeitet)
                        <span class="pill pill-orange">⚠ Nachträglich bearbeitet</span>
                    @endif
                </div>

                {{-- IHK Kopftabelle --}}
                <div style="padding:16px 20px;border-bottom:1px solid var(--p-border)">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <tr>
                            <td style="padding:6px 10px;border:1px solid var(--p-border);color:var(--p-muted);width:220px">Name des/der Auszubildenden:</td>
                            <td style="padding:6px 10px;border:1px solid var(--p-border);font-weight:500">{{ auth()->user()->full_name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 10px;border:1px solid var(--p-border);color:var(--p-muted)">Ausbildungswoche vom:</td>
                            <td style="padding:6px 10px;border:1px solid var(--p-border)">
                                {{ $vorschau->startdatum->format('d.m.Y') }}
                                <span style="color:var(--p-muted);margin:0 8px">bis</span>
                                {{ $vorschau->enddatum->format('d.m.Y') }}
                                <span style="color:var(--p-muted);margin-left:12px">KW {{ $vorschau->startdatum->format('W / Y') }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- IHK Sektionen --}}
                @foreach([
                    'betrieb'      => 'Betriebliche Tätigkeiten',
                    'unterweisung' => 'Unterweisungen bzw. überbetriebliche Unterweisungen, betrieblicher Unterricht, sonstige Schulungen',
                    'berufsschule' => 'Themen des Berufsschulunterrichts',
                ] as $key => $label)
                    @php
                        $kat     = $vorschau->eintraege->where('kategorie', $key);
                        $aufgaben = $aufgabenVorschau->get($key, collect());
                        $summe   = $kat->sum('stunden') + $aufgaben->sum('stunden');
                    @endphp
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr>
                                <th style="padding:8px 14px;background:#E8EDF4;border:1px solid var(--p-border);text-align:left;font-weight:600;font-size:12.5px;color:var(--p-text)">{{ $label }}</th>
                                <th style="padding:8px 14px;background:#E8EDF4;border:1px solid var(--p-border);text-align:right;font-style:italic;font-weight:400;font-size:12.5px;color:var(--p-muted);width:90px">Stunden</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kat as $e)
                                <tr>
                                    <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none">{{ $e->beschreibung }}</td>
                                    <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;color:var(--p-muted)">{{ number_format($e->stunden, 2) }}</td>
                                </tr>
                            @endforeach
                            @foreach($aufgaben as $a)
                                <tr style="background:var(--p-blue-bg)">
                                    <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none;color:var(--p-blue)">
                                        {{ $a->titel }}
                                        <span style="font-size:11px;opacity:.7;margin-left:4px">({{ $a->status === 'fertig' ? 'Fertig' : 'In Bearbeitung' }})</span>
                                    </td>
                                    <td style="padding:7px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;color:var(--p-blue)">{{ number_format($a->stunden, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($kat->count() === 0 && $aufgaben->count() === 0)
                                <tr><td colspan="2" style="padding:14px;border:1px solid var(--p-border);border-top:none;color:var(--p-subtle);height:50px;vertical-align:top"> </td></tr>
                            @else
                                <tr>
                                    <td style="padding:6px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;font-size:12px;color:var(--p-muted)">Gesamt:</td>
                                    <td style="padding:6px 14px;border:1px solid var(--p-border);border-top:none;text-align:right;font-weight:600">{{ number_format($summe, 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                @endforeach

                {{-- Unterschriften --}}
                <div style="padding:16px 20px;border-top:1px solid var(--p-border)">
                    <p style="font-size:12px;color:var(--p-muted);margin-bottom:16px">Durch die nachfolgende Unterschrift wird die Richtigkeit und Vollständigkeit der obigen Angaben bestätigt.</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px">
                        <div>
                            <div style="height:40px;border-bottom:1px solid var(--p-border);margin-bottom:6px;display:flex;align-items:center">
                                @if($vorschau->azubi_signiert)
                                    <span class="pill pill-green">✓ Von dir signiert {{ $vorschau->azubi_signiert_am?->format('d.m.Y') }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:var(--p-muted)">Datum, Unterschrift Auszubildende/r</div>
                        </div>
                        <div>
                            <div style="height:40px;border-bottom:1px solid var(--p-border);margin-bottom:6px;display:flex;align-items:center">
                                @if($vorschau->signiert)
                                    <span class="pill pill-green">✓ Vom Ausbilder signiert {{ $vorschau->signiert_am?->format('d.m.Y') }}</span>
                                @endif
                            </div>
                            <div style="font-size:12px;color:var(--p-muted)">Datum, Unterschrift Ausbildende/r oder Ausbilder/in</div>
                        </div>
                    </div>
                </div>

                {{-- Bearbeitungshistorie --}}
                @if($vorschau->bearbeitungen && $vorschau->bearbeitungen->count())
                    <div style="padding:16px 20px;border-top:1px solid var(--p-border);background:var(--p-orange-bg)">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                            <span style="font-size:13px;font-weight:600;color:var(--p-orange);display:flex;align-items:center;gap:6px">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Bearbeitungshistorie
                            </span>
                            <span style="font-size:12px;color:var(--p-orange)">{{ $vorschau->bearbeitungen->count() }} {{ $vorschau->bearbeitungen->count() === 1 ? 'Eintrag' : 'Einträge' }}</span>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            @foreach($vorschau->bearbeitungen as $b)
                                <div style="padding:10px 14px;background:var(--p-surface);border:1px solid var(--p-orange);border-radius:var(--p-rsm)">
                                    <div style="font-size:13px;font-weight:600;color:var(--p-text)">{{ $b->autor->full_name }}</div>
                                    <div style="font-size:13px;color:var(--p-text);margin-top:3px">{{ $b->kommentar }}</div>
                                    <div style="font-size:11.5px;color:var(--p-orange);margin-top:4px">{{ $b->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Aktionen --}}
                <div class="action-bar">
                    <a href="{{ route('wochenbericht.show', $vorschau->id) }}" class="btn btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Bearbeiten
                    </a>
                    @if(!$vorschau->gesperrt)
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
                    @endif
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

        {{-- ── Berichte-Liste ── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg>
                    Vergangene Berichte
                </span>
            </div>

            {{-- Filter-Bar --}}
            <div style="display:flex;flex-wrap:wrap;gap:10px;padding:16px 20px;border-bottom:1px solid var(--p-border);align-items:flex-end">
                <div style="display:flex;flex-direction:column;gap:4px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--p-muted)">Jahr</div>
                    <select id="filter-jahr" onchange="filterUpdate()" style="padding:7px 10px;border:1px solid var(--p-border);border-radius:var(--p-rsm);background:var(--p-bg);color:var(--p-text);font-size:13px;font-family:var(--p-font);cursor:pointer;outline:none">
                        <option value="">Alle Jahre</option>
                        @foreach($jahre as $jahr)
                            <option value="{{ $jahr }}">{{ $jahr }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--p-muted)">Monate</div>
                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                        @foreach(['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'] as $i => $m)
                            <button class="month-btn" data-month="{{ $i + 1 }}" onclick="toggleMonth(this)">{{ $m }}</button>
                        @endforeach
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--p-muted)">Azubi signiert</div>
                    <div style="display:flex;gap:4px">
                        <button class="month-btn" data-sig="azubi" data-val="ja" onclick="toggleSig(this)">Ja</button>
                        <button class="month-btn" data-sig="azubi" data-val="nein" onclick="toggleSig(this)">Nein</button>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--p-muted)">Ausbilder signiert</div>
                    <div style="display:flex;gap:4px">
                        <button class="month-btn" data-sig="ausbilder" data-val="ja" onclick="toggleSig(this)">Ja</button>
                        <button class="month-btn" data-sig="ausbilder" data-val="nein" onclick="toggleSig(this)">Nein</button>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-left:auto;align-self:flex-end">
                    <button class="btn btn-ghost" onclick="filterReset()">Zurücksetzen</button>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-sammel" style="display:inline">
                        @csrf
                        <input type="hidden" name="ids" id="sammel-ids">
                        <button type="submit" class="btn btn-ghost" id="btn-sammel" onclick="setSammelIds()">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Sammel-PDF
                        </button>
                    </form>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-hj1" style="display:inline">
                        @csrf
                        <input type="hidden" name="ids" id="hj1-ids">
                        <button type="submit" class="btn btn-ghost" id="btn-hj1" disabled
                                style="opacity:.35;cursor:not-allowed"
                                onclick="return setHalbjahrIds(1)">1. Halbjahr PDF</button>
                    </form>
                    <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-hj2" style="display:inline">
                        @csrf
                        <input type="hidden" name="ids" id="hj2-ids">
                        <button type="submit" class="btn btn-ghost" id="btn-hj2" disabled
                                style="opacity:.35;cursor:not-allowed"
                                onclick="return setHalbjahrIds(2)">2. Halbjahr PDF</button>
                    </form>
                </div>
            </div>

            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:13px" id="berichte-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);background:var(--p-bg);border-bottom:1px solid var(--p-border)">KW</th>
                            <th style="padding:9px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);background:var(--p-bg);border-bottom:1px solid var(--p-border)">Zeitraum</th>
                            <th style="padding:9px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);background:var(--p-bg);border-bottom:1px solid var(--p-border)">Status</th>
                            <th style="padding:9px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--p-muted);background:var(--p-bg);border-bottom:1px solid var(--p-border)">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vergangen as $bericht)
                            <tr data-jahr="{{ $bericht->startdatum->year }}"
                                data-monat="{{ $bericht->startdatum->month }}"
                                data-id="{{ $bericht->id }}"
                                data-azubi-sig="{{ $bericht->azubi_signiert ? 'ja' : 'nein' }}"
                                data-ausbilder-sig="{{ $bericht->signiert ? 'ja' : 'nein' }}"
                                style="border-bottom:1px solid var(--p-border)">
                                <td style="padding:11px 16px;font-weight:500">KW {{ $bericht->startdatum->format('W / Y') }}</td>
                                <td style="padding:11px 16px;color:var(--p-muted);font-size:12px">
                                    {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                                </td>
                                <td style="padding:11px 16px">
                                    <div style="display:flex;flex-wrap:wrap;gap:4px">
                                        @if($bericht->gesperrt)
                                            <span class="pill pill-dark">🔒 Gesperrt</span>
                                        @elseif($bericht->signiert)
                                            <span class="pill pill-green">✅ Signiert</span>
                                        @elseif($bericht->azubi_signiert)
                                            <span class="pill pill-blue">✍ Von dir signiert</span>
                                        @elseif($bericht->abgegeben)
                                            <span class="pill pill-amber">📤 Abgegeben</span>
                                        @else
                                            <span class="pill pill-gray">✏️ In Bearbeitung</span>
                                        @endif
                                        @if($bericht->bearbeitet)
                                            <span class="pill pill-orange">⚠️ Bearbeitet</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding:11px 16px">
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <a href="{{ route('wochenbericht.index', ['vorschau' => $bericht->id]) }}"
                                           style="padding:5px 10px;background:var(--p-blue-bg);color:var(--p-blue);border-radius:6px;font-size:12px;text-decoration:none">
                                            Vorschau
                                        </a>
                                        <a href="{{ route('wochenbericht.pdf', $bericht->id) }}"
                                           style="padding:5px 10px;background:var(--p-bg);border:1px solid var(--p-border);color:var(--p-muted);border-radius:6px;font-size:12px;text-decoration:none">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:24px;text-align:center;color:var(--p-muted)">Keine vergangenen Berichte.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="keine-ergebnisse" style="display:none;padding:24px;text-align:center;color:var(--p-muted);font-size:13px">Keine Berichte für diese Filterauswahl.</div>
            </div>
        </div>

    </div>
</div>

<style>
.month-btn { padding: 5px 9px; border: 1px solid var(--p-border); border-radius: 6px; font-size: 12px; font-family: var(--p-font); background: var(--p-bg); color: var(--p-muted); cursor: pointer; transition: all .1s; }
.month-btn.sel { background: var(--p-blue); color: #fff; border-color: var(--p-blue); }
</style>

<script>
let selectedMonths = new Set();
let sigFilter = { azubi: null, ausbilder: null };

function toggleMonth(btn) {
    const m = parseInt(btn.dataset.month);
    if (selectedMonths.has(m)) { selectedMonths.delete(m); btn.classList.remove('sel'); }
    else { selectedMonths.add(m); btn.classList.add('sel'); }
    filterUpdate();
}

function toggleSig(btn) {
    const type = btn.dataset.sig;
    const val  = btn.dataset.val;
    if (sigFilter[type] === val) {
        sigFilter[type] = null;
        btn.classList.remove('sel');
    } else {
        sigFilter[type] = val;
        document.querySelectorAll('[data-sig="' + type + '"]').forEach(b => b.classList.remove('sel'));
        btn.classList.add('sel');
    }
    filterUpdate();
}

function filterUpdate() {
    const jahr = document.getElementById('filter-jahr').value;
    const rows = document.querySelectorAll('#berichte-table tbody tr[data-id]');
    let visible = 0;
    rows.forEach(row => {
        const jahrOk = !jahr || row.dataset.jahr === jahr;
        const monatOk = selectedMonths.size === 0 || selectedMonths.has(parseInt(row.dataset.monat));
        const azubiOk = !sigFilter.azubi || row.dataset.azubiSig === sigFilter.azubi;
        const ausbilderOk = !sigFilter.ausbilder || row.dataset.ausbilderSig === sigFilter.ausbilder;
        const show = jahrOk && monatOk && azubiOk && ausbilderOk;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('keine-ergebnisse').style.display = visible === 0 ? 'block' : 'none';
    const hj = !!jahr;
    ['btn-hj1','btn-hj2'].forEach(id => {
        const btn = document.getElementById(id);
        btn.disabled = !hj;
        btn.style.opacity = hj ? '1' : '.35';
        btn.style.cursor = hj ? 'pointer' : 'not-allowed';
    });
}

function setHalbjahrIds(hj) {
    if (document.getElementById('btn-hj' + hj).disabled) return false;
    const jahr = document.getElementById('filter-jahr').value;
    const monate = hj === 1 ? [1,2,3,4,5,6] : [7,8,9,10,11,12];
    const ids = [...document.querySelectorAll('#berichte-table tbody tr[data-id]')]
        .filter(r => r.dataset.jahr === jahr && monate.includes(parseInt(r.dataset.monat)))
        .map(r => r.dataset.id);
    document.getElementById('hj' + hj + '-ids').value = ids.join(',');
    return true;
}
</script>

</x-app-layout>
