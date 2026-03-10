<div class="border rounded-lg p-3 bg-gray-50">
    <div class="flex justify-between items-start mb-2">
        <span class="font-medium text-sm">{{ $aufgabe->titel }}</span>
        <span class="text-xs text-gray-500">{{ $aufgabe->stunden }} Std</span>
    </div>

    @if($aufgabe->beschreibung)
        <p class="text-xs text-gray-500 mb-2">{{ $aufgabe->beschreibung }}</p>
    @endif

    <span class="text-xs px-2 py-0.5 rounded
        {{ $aufgabe->kategorie === 'betrieb' ? 'bg-blue-100 text-blue-700' : '' }}
        {{ $aufgabe->kategorie === 'berufsschule' ? 'bg-purple-100 text-purple-700' : '' }}
        {{ $aufgabe->kategorie === 'unterweisung' ? 'bg-yellow-100 text-yellow-700' : '' }}">
        {{ ucfirst($aufgabe->kategorie) }}
    </span>

    {{-- Status verschieben --}}
    <div class="mt-3 flex flex-wrap gap-1">
        @if($aufgabe->status !== 'geplant')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="geplant">
                <button class="text-xs px-2 py-1 bg-gray-200 rounded hover:bg-gray-300">← Geplant</button>
            </form>
        @endif

        @if($aufgabe->status !== 'in_bearbeitung')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="in_bearbeitung">
                <button class="text-xs px-2 py-1 bg-yellow-200 rounded hover:bg-yellow-300">In Bearbeitung</button>
            </form>
        @endif

        @if($aufgabe->status !== 'fertig')
            <form method="POST" action="{{ route('aufgaben.status', $aufgabe->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="fertig">
                <button class="text-xs px-2 py-1 bg-green-200 rounded hover:bg-green-300">Fertig ✓</button>
            </form>
        @endif
    </div>

    {{-- Aktionen --}}
    <div class="mt-2 flex gap-2 border-t pt-2">
        <form method="POST" action="{{ route('aufgaben.archivieren', $aufgabe->id) }}">
            @csrf @method('PATCH')
            <button class="text-xs text-gray-400 hover:text-gray-600">Archivieren</button>
        </form>
        
        <button onclick="bearbeitenModal({{ $aufgabe->id }}, '{{ addslashes($aufgabe->titel) }}', '{{ addslashes($aufgabe->beschreibung) }}', '{{ $aufgabe->kategorie }}', '{{ $aufgabe->stunden }}', '{{ $aufgabe->stunden_abziehen_bei }}')"
          class="text-xs text-blue-400 hover:text-blue-600">Bearbeiten</button>

        <form method="POST" action="{{ route('aufgaben.destroy', $aufgabe->id) }}"
            onsubmit="return confirm('Aufgabe wirklich löschen?')">
            @csrf @method('DELETE')
            <button class="text-xs text-red-400 hover:text-red-600">Löschen</button>
        </form>
    </div>
</div>