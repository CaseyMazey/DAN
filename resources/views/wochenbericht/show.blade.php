<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wochenbericht KW {{ $bericht->startdatum->format('W') }}
                — {{ $bericht->startdatum->format('d.m.Y') }}
                bis {{ $bericht->enddatum->format('d.m.Y') }}
            </h2>
            <a href="{{ route('wochenbericht.index') }}" class="text-sm text-blue-600 hover:underline">
                ← Alle Berichte
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Meldungen --}}
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Status & Aktionen --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-wrap gap-3 items-center">
                    @if($bericht->gesperrt)
                        <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-sm">🔒 Gesperrt</span>
                    @elseif($bericht->signiert)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">✅ Signiert</span>
                    @elseif($bericht->azubi_signiert)
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">✍️ Von dir signiert</span>
                    @elseif($bericht->abgegeben)
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">📤 Abgegeben</span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">✏️ In Bearbeitung</span>
                    @endif

                    @if($bericht->bearbeitet)
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm">⚠️ Nachträglich bearbeitet</span>
                    @endif

                    @if(!$bericht->gesperrt)
                        @if(!$bericht->abgegeben)
                            <form method="POST" action="{{ route('wochenbericht.abgeben', $bericht->id) }}">
                                @csrf
                                <button class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
                                    Abgeben
                                </button>
                            </form>
                        @endif

                        @if($bericht->abgegeben && !$bericht->azubi_signiert)
                            <form method="POST" action="{{ route('wochenbericht.signieren', $bericht->id) }}">
                                @csrf
                                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                    Selbst signieren
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Drei IHK-Kategorien --}}
            @foreach([
                'betrieb'      => 'Betriebliche Tätigkeiten',
                'unterweisung' => 'Unterweisungen / Schulungen',
                'berufsschule' => 'Themen des Berufsschulunterrichts'
            ] as $key => $label)
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">{{ $label }}</h3>

                    {{-- Vorhandene Einträge --}}
                    @if(isset($eintraege[$key]) && $eintraege[$key]->count())
                        <div class="space-y-2 mb-4">
                            @foreach($eintraege[$key] as $eintrag)
                                <div class="flex justify-between items-start p-3 bg-gray-50 rounded">
                                    <p class="text-sm text-gray-800">{{ $eintrag->beschreibung }}</p>
                                    <span class="text-sm text-gray-500 ml-4 whitespace-nowrap">{{ $eintrag->stunden }} Std</span>
                                </div>
                            @endforeach
                            <div class="text-right text-sm text-gray-500 font-medium">
                                Gesamt: {{ $eintraege[$key]->sum('stunden') }} Std
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 mb-4">Noch keine Einträge.</p>
                    @endif

                    {{-- Neuen Eintrag hinzufügen --}}
                    @if(!$bericht->gesperrt)
                        <form method="POST" action="{{ route('wochenbericht.speichern', $bericht->id) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="kategorie" value="{{ $key }}">

                            <div class="flex gap-3">
                                <textarea name="beschreibung" rows="2"
                                    placeholder="Was wurde gemacht?"
                                    class="border rounded px-3 py-2 text-sm flex-1"></textarea>
                                <input type="number" name="stunden" step="0.5" min="0" max="99"
                                    placeholder="Std"
                                    class="border rounded px-3 py-2 text-sm w-20">
                            </div>

                            @if($bericht->abgegeben)
                                <textarea name="bearbeitungs_kommentar" rows="2"
                                    placeholder="Pflicht: Was wurde nachträglich geändert?"
                                    class="border rounded px-3 py-2 text-sm w-full border-orange-300"></textarea>
                            @endif

                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                + Eintrag hinzufügen
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach

            {{-- Gesamtstunden --}}
            <div class="bg-white shadow rounded-lg p-4 text-right">
                <span class="text-sm text-gray-500">Gesamtstunden diese Woche:</span>
                <span class="ml-2 font-semibold text-lg">{{ $bericht->gesamtstunden() }} Std</span>
            </div>

            {{-- Kommentare vom Ausbilder --}}
            @if($kommentare->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Kommentare</h3>
                    <div class="space-y-3">
                        @foreach($kommentare as $kommentar)
                            <div class="p-3 bg-blue-50 rounded">
                                <div class="text-xs text-gray-500 mb-1">
                                    {{ $kommentar->autor->name }} —
                                    {{ $kommentar->created_at->format('d.m.Y H:i') }}
                                </div>
                                <p class="text-sm">{{ $kommentar->inhalt }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Bearbeitungshistorie --}}
            @if($bearbeitungen->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Bearbeitungshistorie</h3>
                    <div class="space-y-3">
                        @foreach($bearbeitungen as $bearbeitung)
                            <div class="p-3 bg-orange-50 rounded">
                                <div class="text-xs text-gray-500 mb-1">
                                    {{ $bearbeitung->created_at->format('d.m.Y H:i') }}
                                </div>
                                <p class="text-sm">{{ $bearbeitung->kommentar }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>