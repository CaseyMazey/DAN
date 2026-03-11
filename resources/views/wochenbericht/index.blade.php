<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Wochenberichte</h2>
            <form method="POST" action="{{ route('wochenbericht.fehlend') }}">
                @csrf
                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">
                    🔍 Fehlende Berichte erstellen
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Aktueller Bericht --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">Aktueller Bericht</h3>
                    @if($aktuell)
                        <span class="text-sm text-gray-500">
                            KW {{ $aktuell->startdatum->format('W') }} —
                            {{ $aktuell->startdatum->format('d.m.Y') }} bis
                            {{ $aktuell->enddatum->format('d.m.Y') }}
                        </span>
                    @endif
                </div>

                @if($aktuell)
                    {{-- Status-Badges --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($aktuell->gesperrt)
                            <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-xs">🔒 Gesperrt</span>
                        @elseif($aktuell->signiert)
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs">✅ Ausbilder signiert</span>
                        @elseif($aktuell->azubi_signiert)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">✍️ Von dir signiert</span>
                        @elseif($aktuell->abgegeben)
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">📤 Abgegeben</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">✏️ In Bearbeitung</span>
                        @endif
                        @if($aktuell->bearbeitet)
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs">⚠️ Bearbeitet</span>
                        @endif
                    </div>

                    {{-- Vorschau Einträge --}}
                    @foreach(['betrieb' => 'Betriebliche Tätigkeiten', 'unterweisung' => 'Unterweisungen', 'berufsschule' => 'Berufsschule'] as $key => $label)
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ $label }}</p>

                            @php
                                $eintraege = $aktuell->eintraege->where('kategorie', $key);
                                $aufgaben = $aufgabenVorschau->get($key, collect());
                            @endphp

                            @if($eintraege->count())
                                @foreach($eintraege as $e)
                                    <div class="flex justify-between text-sm p-2 bg-gray-50 rounded mb-1">
                                        <span>{{ $e->beschreibung }}</span>
                                        <span class="text-gray-400 ml-4">{{ $e->stunden }} Std</span>
                                    </div>
                                @endforeach
                            @endif

                            @if($aufgaben->count())
                                @foreach($aufgaben as $a)
                                    <div class="flex justify-between text-sm p-2 bg-blue-50 rounded mb-1 border border-blue-100">
                                        <span class="text-blue-800">
                                            {{ $a->titel }}
                                            <span class="text-xs text-blue-400 ml-1">({{ $a->status === 'fertig' ? 'Fertig' : 'In Bearbeitung' }})</span>
                                        </span>
                                        <span class="text-blue-400 ml-4">{{ $a->stunden }} Std</span>
                                    </div>
                                @endforeach
                            @endif

                            @if($eintraege->count() === 0 && $aufgaben->count() === 0)
                                <p class="text-xs text-gray-400">Keine Einträge.</p>
                            @endif
                        </div>
                    @endforeach

                    {{-- Aktionen --}}
                    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t">
                        <a href="{{ route('wochenbericht.show', $aktuell->id) }}"
                            class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            ✏️ Bearbeiten
                        </a>

                        @if(!$aktuell->abgegeben)
                            <form method="POST" action="{{ route('wochenbericht.abgeben', $aktuell->id) }}">
                                @csrf
                                <button class="px-4 py-2 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600">
                                    📤 Abgeben
                                </button>
                            </form>
                        @endif

                        @if($aktuell->abgegeben && !$aktuell->azubi_signiert)
                            <form method="POST" action="{{ route('wochenbericht.signieren', $aktuell->id) }}">
                                @csrf
                                <button class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                    ✍️ Signieren
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('wochenbericht.pdf', $aktuell->id) }}"
                            class="px-4 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            📄 PDF
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Kein Bericht für diese Woche vorhanden.</p>
                @endif
            </div>

            {{-- Vergangene Berichte --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">Vergangene Berichte</h3>
                    <span class="text-xs text-gray-400">Max. 10 Einträge</span>
                </div>

                {{-- Filter --}}
                <form method="GET" action="{{ route('wochenbericht.index') }}" class="flex flex-wrap gap-3 mb-4">
                    <select name="jahr" class="border rounded px-3 py-2 text-sm">
                        <option value="">Alle Jahre</option>
                        @foreach($jahre as $jahr)
                            <option value="{{ $jahr }}" {{ request('jahr') == $jahr ? 'selected' : '' }}>{{ $jahr }}</option>
                        @endforeach
                    </select>
                    <select name="monat" class="border rounded px-3 py-2 text-sm">
                        <option value="">Alle Monate</option>
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ request('monat') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('de')->monthName }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" name="kw" placeholder="KW" value="{{ request('kw') }}"
                        class="border rounded px-3 py-2 text-sm w-20">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="nur_signierte" value="1" {{ request('nur_signierte') ? 'checked' : '' }}>
                        Nur signierte
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Filtern</button>
                    <a href="{{ route('wochenbericht.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Zurücksetzen</a>
                </form>

                {{-- Sammel-PDF --}}
                <form method="POST" action="{{ route('wochenbericht.sammel') }}" id="form-sammel">
                    @csrf
                    <input type="hidden" name="ids" id="sammel-ids">

                    <div class="flex gap-2 mb-3">
                        <button type="button" onclick="alleAuswaehlen()"
                            class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs hover:bg-gray-200">
                            Alle auswählen
                        </button>
                        <button type="submit"
                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs hover:bg-blue-200">
                            📦 Auswahl als Sammel-PDF
                        </button>
                    </div>

                    {{-- Tabelle --}}
                    @forelse($vergangen as $bericht)
                        <div class="flex items-center justify-between p-3 border rounded-lg mb-2 hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" class="sammel-check" value="{{ $bericht->id }}">
                                <div>
                                    <p class="text-sm font-medium">
                                        KW {{ $bericht->startdatum->format('W / Y') }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- Azubi Signatur --}}
                                @if($bericht->azubi_signiert)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Azubi ✓</span>
                                @else
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">Azubi ✗</span>
                                @endif

                                {{-- Ausbilder Signatur --}}
                                @if($bericht->signiert)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Ausbilder ✓</span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">Ausbilder ✗</span>
                                @endif

                                {{-- Bearbeitet Tag --}}
                                @if($bericht->bearbeitet)
                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">⚠️ Bearbeitet</span>
                                @endif

                                <a href="{{ route('wochenbericht.show', $bericht->id) }}"
                                    class="px-3 py-1 bg-blue-50 text-blue-600 rounded text-xs hover:bg-blue-100">
                                    Bearbeiten
                                </a>
                                <a href="{{ route('wochenbericht.pdf', $bericht->id) }}"
                                    class="px-3 py-1 bg-gray-100 text-gray-600 rounded text-xs hover:bg-gray-200">
                                    PDF
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Keine vergangenen Berichte.</p>
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
        if (ids.length === 0) {
            e.preventDefault();
            alert('Bitte mindestens einen Bericht auswählen.');
            return;
        }
        document.getElementById('sammel-ids').value = ids.join(',');
    });
    </script>

</x-app-layout>