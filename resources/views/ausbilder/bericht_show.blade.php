<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wochenbericht KW {{ $bericht->startdatum->format('W') }} —
                {{ $bericht->azubi->full_name }}
            </h2>
            <a href="{{ route('ausbilder.azubi.berichte', $bericht->azubi_id) }}"
                class="text-sm text-gray-500 hover:text-gray-700">← Alle Berichte</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Status --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-wrap gap-2 mb-2">
                    @if($bericht->gesperrt)
                        <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full text-xs">🔒 Gesperrt</span>
                    @elseif($bericht->signiert)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs">✅ Von dir signiert</span>
                    @elseif($bericht->azubi_signiert)
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">⏳ Wartet auf deine Signatur</span>
                    @elseif($bericht->abgegeben)
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">📤 Abgegeben</span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">✏️ In Bearbeitung</span>
                    @endif
                    @if($bericht->bearbeitet)
                        <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs">⚠️ Nachträglich bearbeitet</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                </p>
            </div>

            {{-- Einträge --}}
            @foreach(['betrieb' => 'Betriebliche Tätigkeiten', 'unterweisung' => 'Unterweisungen', 'berufsschule' => 'Berufsschule'] as $key => $label)
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-base mb-3">{{ $label }}</h3>
                    @if(isset($eintraege[$key]) && $eintraege[$key]->count())
                        @foreach($eintraege[$key] as $eintrag)
                            <div class="flex justify-between text-sm p-2 bg-gray-50 rounded mb-1">
                                <span>{{ $eintrag->beschreibung }}</span>
                                <span class="text-gray-400 ml-4 shrink-0">{{ $eintrag->stunden }} Std</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400">Keine Einträge.</p>
                    @endif
                </div>
            @endforeach

            {{-- Bearbeitungshistorie --}}
            @if($bericht->bearbeitungen->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-base mb-3">⚠️ Bearbeitungshistorie</h3>
                    <div class="space-y-2">
                        @foreach($bericht->bearbeitungen as $bearbeitung)
                            <div class="p-3 bg-orange-50 border border-orange-100 rounded text-sm">
                                <p class="font-medium text-orange-800">{{ $bearbeitung->autor->full_name }}</p>
                                <p class="text-orange-700 mt-1">{{ $bearbeitung->kommentar }}</p>
                                <p class="text-xs text-orange-400 mt-1">{{ $bearbeitung->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Kommentare --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-base mb-3">💬 Kommentare</h3>

                @if($bericht->kommentare->count())
                    <div class="space-y-2 mb-4">
                        @foreach($bericht->kommentare as $kommentar)
                            <div class="p-3 bg-gray-50 rounded text-sm">
                                <p class="font-medium text-gray-700">{{ $kommentar->autor->full_name }}</p>
                                <p class="text-gray-600 mt-1">{{ $kommentar->inhalt }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $kommentar->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!$bericht->gesperrt)
                    <form method="POST" action="{{ route('ausbilder.bericht.kommentieren', $bericht->id) }}">
                        @csrf
                        <textarea name="inhalt" rows="3" required
                            placeholder="Kommentar schreiben..."
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2"></textarea>
                        <button class="px-4 py-2 bg-gray-600 text-white rounded text-sm hover:bg-gray-700">
                            💬 Kommentar senden
                        </button>
                    </form>
                @endif
            </div>

            {{-- Signieren --}}
            @if(!$bericht->signiert && !$bericht->gesperrt && $bericht->azubi_signiert)
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-base mb-2">✍️ Bericht signieren</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Mit deiner Signatur wird der Bericht gesperrt und kann nicht mehr bearbeitet werden.
                    </p>
                    <form method="POST" action="{{ route('ausbilder.bericht.signieren', $bericht->id) }}"
                        onsubmit="return confirm('Bericht wirklich signieren und sperren?')">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                            ✍️ Jetzt signieren & sperren
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
