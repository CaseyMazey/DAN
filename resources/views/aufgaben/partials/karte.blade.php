{{-- resources/views/aufgaben/partials/karte.blade.php --}}
<div style="background:var(--p-bg);border:1px solid var(--p-border);border-radius:var(--p-rsm);padding:11px 12px;transition:border-color .12s,box-shadow .12s"
     onmouseover="this.style.borderColor='var(--p-blue)';this.style.boxShadow='0 1px 6px rgba(37,99,235,.08)'"
     onmouseout="this.style.borderColor='var(--p-border)';this.style.boxShadow='none'">

    {{-- Title + hours --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px">
        <span style="font-size:13px;font-weight:600;line-height:1.35">{{ $aufgabe->titel }}</span>
        <span style="font-size:12px;color:var(--p-muted);white-space:nowrap;padding-top:1px;flex-shrink:0">{{ $aufgabe->stunden }} Std</span>
    </div>

    {{-- Description --}}
    @if($aufgabe->beschreibung)
        <p style="font-size:12px;color:var(--p-muted);margin-bottom:7px;line-height:1.4">{{ Str::limit($aufgabe->beschreibung, 80) }}</p>
    @endif

    {{-- Category pill --}}
    <div style="margin-bottom:8px">
        @if($aufgabe->kategorie === 'betrieb')
            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:500;background:var(--p-blue-bg);color:var(--p-blue)">Betrieb</span>
        @elseif($aufgabe->kategorie === 'berufsschule')
            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:500;background:var(--p-purple-bg);color:var(--p-purple)">Berufsschule</span>
        @else
            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:500;background:var(--p-amber-bg);color:var(--p-amber)">Unterweisung</span>
        @endif
    </div>

    {{-- Move buttons --}}
    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
        @if($aufgabe->status !== 'geplant')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="geplant">
                <button style="display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:500;cursor:pointer;border:1px solid var(--p-border);background:var(--p-surface);color:var(--p-muted);font-family:var(--p-font);transition:all .1s"
                    onmouseover="this.style.borderColor='var(--p-blue)';this.style.color='var(--p-blue)'"
                    onmouseout="this.style.borderColor='var(--p-border)';this.style.color='var(--p-muted)'">
                    ← Geplant
                </button>
            </form>
        @endif
        @if($aufgabe->status !== 'in_bearbeitung')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_bearbeitung">
                <button style="display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:500;cursor:pointer;border:1px solid var(--p-amber);background:var(--p-amber-bg);color:var(--p-amber);font-family:var(--p-font)">
                    In Bearbeitung
                </button>
            </form>
        @endif
        @if($aufgabe->status !== 'fertig')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="fertig">
                <button style="display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:500;cursor:pointer;border:1px solid var(--p-green);background:var(--p-green-bg);color:var(--p-green);font-family:var(--p-font)">
                    ✓ Fertig
                </button>
            </form>
        @endif
    </div>

    {{-- Actions --}}
    <div style="display:flex;gap:2px;padding-top:8px;border-top:1px solid var(--p-border)">
        <button onclick="bearbeitenModal({{ $aufgabe->id }}, '{{ addslashes($aufgabe->titel) }}', '{{ addslashes($aufgabe->beschreibung ?? '') }}', '{{ $aufgabe->kategorie }}', '{{ $aufgabe->stunden }}', '{{ $aufgabe->stunden_abziehen_bei }}')"
            style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:var(--p-rsm);font-size:11.5px;font-weight:500;cursor:pointer;border:none;background:transparent;color:var(--p-blue);font-family:var(--p-font);transition:background .1s"
            onmouseover="this.style.background='var(--p-blue-bg)'"
            onmouseout="this.style.background='transparent'">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Bearbeiten
        </button>

        <form method="POST" action="{{ route('aufgaben.archivieren', $aufgabe->id) }}">
            @csrf @method('PATCH')
            <button style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:var(--p-rsm);font-size:11.5px;font-weight:500;cursor:pointer;border:none;background:transparent;color:var(--p-muted);font-family:var(--p-font);transition:background .1s"
                onmouseover="this.style.background='var(--p-gray-bg)';this.style.color='var(--p-text)'"
                onmouseout="this.style.background='transparent';this.style.color='var(--p-muted)'">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/></svg>
                Archivieren
            </button>
        </form>

        <form method="POST" action="{{ route('aufgaben.destroy', $aufgabe->id) }}"
            onsubmit="return confirm('Aufgabe wirklich löschen?')">
            @csrf @method('DELETE')
            <button style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:var(--p-rsm);font-size:11.5px;font-weight:500;cursor:pointer;border:none;background:transparent;color:var(--p-red);font-family:var(--p-font);transition:background .1s"
                onmouseover="this.style.background='var(--p-red-bg)'"
                onmouseout="this.style.background='transparent'">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                Löschen
            </button>
        </form>
    </div>

</div>
