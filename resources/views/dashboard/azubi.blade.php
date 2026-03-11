<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Willkommen, {{ auth()->user()->full_name }}! 👋
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Hinweis-Karten --}}
            @if($nichtSigniert > 0)
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-3">
                    <span class="text-yellow-500 text-xl">✍️</span>
                    <div>
                        <p class="font-medium text-yellow-800">{{ $nichtSigniert }} Bericht{{ $nichtSigniert > 1 ? 'e' : '' }} warten auf deine Signatur</p>
                        <a href="{{ route('wochenbericht.index') }}" class="text-sm text-yellow-600 hover:underline">Jetzt signieren →</a>
                    </div>
                </div>
            @endif

            @if($ausstehendAusbilder > 0)
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
                    <span class="text-blue-500 text-xl">⏳</span>
                    <div>
                        <p class="font-medium text-blue-800">{{ $ausstehendAusbilder }} Bericht{{ $ausstehendAusbilder > 1 ? 'e' : '' }} warten auf Ausbilder-Signatur</p>
                        <p class="text-sm text-blue-500">Dein Ausbilder muss diese noch unterschreiben.</p>
                    </div>
                </div>
            @endif

            {{-- Aufgaben-Übersicht --}}
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold text-lg">Meine Aufgaben</h3>
                    <a href="{{ route('aufgaben.index') }}" class="text-sm text-blue-600 hover:underline">Alle anzeigen →</a>
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                    <div class="border rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-gray-700">{{ $aufgabenStats['geplant'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">📋 Geplant</p>
                    </div>
                    <div class="border rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-blue-600">{{ $aufgabenStats['in_bearbeitung'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">🔧 In Bearbeitung</p>
                    </div>
                    <div class="border rounded-lg p-4 text-center">
                        <p class="text-3xl font-bold text-green-600">{{ $aufgabenStats['fertig'] }}</p>
                        <p class="text-sm text-gray-500 mt-1">✅ Fertig</p>
                    </div>
                </div>
            </div>

            {{-- Stunden & Kalender nebeneinander --}}
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">

                {{-- Stunden diese Woche --}}
                <div class="bg-white shadow rounded-lg p-6 flex flex-col justify-center items-center text-center">
                    <p class="text-sm text-gray-500 uppercase font-semibold mb-2">Stunden diese Woche</p>
                    <p class="text-5xl font-bold text-gray-800">{{ $stundenDieseWoche }}</p>
                    <p class="text-sm text-gray-400 mt-1">Std (aktive Aufgaben)</p>
                </div>

                {{-- Nächste Termine --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Nächste Termine</h3>
                        <a href="{{ route('kalender.index') }}" class="text-sm text-blue-600 hover:underline">Alle anzeigen →</a>
                    </div>
                    @forelse($naechsteTermine as $termin)
                        <div class="flex justify-between items-center p-3 border rounded-lg mb-2">
                            <div>
                                <p class="text-sm font-medium">{{ $termin->fach }}</p>
                                <p class="text-xs text-gray-500">{{ ucfirst($termin->typ) }}@if($termin->thema) — {{ $termin->thema }}@endif</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">{{ $termin->datum->format('d.m.Y') }}</p>
                                <p class="text-xs text-gray-400">{{ $termin->datum->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Keine kommenden Termine.</p>
                    @endforelse
                </div>

            </div>

        </div>
    </div>
</x-app-layout>