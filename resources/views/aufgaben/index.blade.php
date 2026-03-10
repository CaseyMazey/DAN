<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Meine Aufgaben</h2>
            <div class="flex gap-2">
                <button onclick="document.getElementById('modal-config').classList.remove('hidden')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">
                    Stunden konfigurieren
                </button>
                <button onclick="document.getElementById('modal-erstellen').classList.remove('hidden')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                    + Neue Aufgabe
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Meldungen --}}
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Stunden-Übersicht --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Betrieb (Soll)</p>
                        <p class="text-2xl font-bold">{{ $config->betrieb_stunden }} Std</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Berufsschule (Soll)</p>
                        <p class="text-2xl font-bold">{{ $config->berufsschule_stunden }} Std</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Gesamt diese Woche</p>
                        <p class="text-2xl font-bold {{ $ueberschritten ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $stundenDieseWoche }} / {{ $config->max_stunden }} Std
                        </p>
                    </div>
                </div>
                @if($ueberschritten)
                    <div class="mt-4 p-3 bg-red-100 text-red-700 rounded text-sm text-center">
                        ⚠️ Du hast deine maximale Stundenanzahl diese Woche überschritten!
                    </div>
                @endif
            </div>

            {{-- Kanban Board --}}
            <div class="grid grid-cols-3 gap-4">

                {{-- Geplant --}}
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-4 text-center">📋 Geplant</h3>
                    <div class="space-y-3">
                        @forelse($aufgaben->get('geplant', collect()) as $aufgabe)
                            @include('aufgaben.partials.karte', ['aufgabe' => $aufgabe])
                        @empty
                            <p class="text-sm text-gray-400 text-center">Keine Aufgaben</p>
                        @endforelse
                    </div>
                </div>

                {{-- In Bearbeitung --}}
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-4 text-center">🔧 In Bearbeitung</h3>
                    <div class="space-y-3">
                        @forelse($aufgaben->get('in_bearbeitung', collect()) as $aufgabe)
                            @include('aufgaben.partials.karte', ['aufgabe' => $aufgabe])
                        @empty
                            <p class="text-sm text-gray-400 text-center">Keine Aufgaben</p>
                        @endforelse
                    </div>
                </div>

                {{-- Fertig --}}
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-semibold text-gray-700 mb-4 text-center">✅ Fertig</h3>
                    <div class="space-y-3">
                        @forelse($aufgaben->get('fertig', collect()) as $aufgabe)
                            @include('aufgaben.partials.karte', ['aufgabe' => $aufgabe])
                        @empty
                            <p class="text-sm text-gray-400 text-center">Keine Aufgaben</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- Archiv --}}
            @if($archiviert->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4">Archiv <span class="text-sm text-gray-400">(max. 20)</span></h3>
                    <div class="space-y-2">
                        @foreach($archiviert as $aufgabe)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="text-sm font-medium">{{ $aufgabe->titel }}</span>
                                    <span class="text-xs text-gray-400 ml-2">{{ ucfirst($aufgabe->kategorie) }} — {{ $aufgabe->stunden }} Std</span>
                                    @if($aufgabe->in_wochenbericht)
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded ml-2">Im Bericht</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('aufgaben.wiederherstellen', $aufgabe->id) }}">
                                    @csrf @method('PATCH')
                                    <button class="text-xs text-blue-600 hover:underline">Wiederherstellen</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Modal: Neue Aufgabe --}}
    <div id="modal-erstellen" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Neue Aufgabe erstellen</h3>
            <form method="POST" action="{{ route('aufgaben.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                    <input type="text" name="titel" class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                    <textarea name="beschreibung" rows="3" class="border rounded px-3 py-2 w-full text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
                    <select name="kategorie" id="kategorie-select" class="border rounded px-3 py-2 w-full text-sm"
                        onchange="document.getElementById('unterweisung-extra').classList.toggle('hidden', this.value !== 'unterweisung')">
                        <option value="betrieb">Betrieb</option>
                        <option value="berufsschule">Berufsschule</option>
                        <option value="unterweisung">Unterweisung / Schulung</option>
                    </select>
                </div>
                <div id="unterweisung-extra" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stunden abziehen bei</label>
                    <select name="stunden_abziehen_bei" class="border rounded px-3 py-2 w-full text-sm">
                        <option value="betrieb">Betrieb</option>
                        <option value="berufsschule">Berufsschule</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stunden</label>
                    <input type="number" name="stunden" step="0.5" min="0.5" max="99"
                        class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-erstellen').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Erstellen</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Stunden Config --}}
    <div id="modal-config" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Stunden konfigurieren</h3>
            <form method="POST" action="{{ route('aufgaben.config') }}" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maximale Stunden pro Woche</label>
                    <input type="number" name="max_stunden" step="0.5" value="{{ $config->max_stunden }}"
                        class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Betrieb Stunden</label>
                    <input type="number" name="betrieb_stunden" step="0.5" value="{{ $config->betrieb_stunden }}"
                        class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berufsschule Stunden</label>
                    <input type="number" name="berufsschule_stunden" step="0.5" value="{{ $config->berufsschule_stunden }}"
                        class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-config').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Speichern</button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>