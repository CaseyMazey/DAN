<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $klasse->name }}
                    @if($klasse->jahrgang)
                        <span class="text-sm text-gray-400 font-normal ml-2">Jahrgang {{ $klasse->jahrgang }}</span>
                    @endif
                </h2>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">← Dashboard</a>
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

            {{-- Azubi hinzufügen --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">➕ Azubi hinzufügen</h3>
                <form method="POST" action="{{ route('ausbilder.klasse.azubi.hinzufuegen', $klasse->id) }}"
                    class="flex gap-3">
                    @csrf
                    <input type="email" name="email" placeholder="E-Mail des Azubis" required
                        class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        Hinzufügen / Einladen
                    </button>
                </form>
                <p class="text-xs text-gray-400 mt-2">
                    Existiert bereits ein Account, wird er direkt hinzugefügt. Ansonsten erhält die Person einen Einladungslink.
                </p>
            </div>

            {{-- Azubi Liste --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Azubis ({{ $azubis->count() }})</h3>

                @if($azubis->count())
                    <div class="overflow-hidden border rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-2 text-left">Name</th>
                                    <th class="px-4 py-2 text-left">Ausbildung</th>
                                    <th class="px-4 py-2 text-left">Letzter Bericht</th>
                                    <th class="px-4 py-2 text-left">Status</th>
                                    <th class="px-4 py-2 text-left">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($azubis as $eintrag)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium">
                                            <a href="{{ route('ausbilder.azubi.profil', $eintrag['azubi']->id) }}"
                                               class="hover:text-blue-600 hover:underline">
                                                {{ $eintrag['azubi']->full_name }}
                                            </a>
                                            <p class="text-xs text-gray-400">{{ $eintrag['azubi']->email }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            @if($eintrag['azubi']->ausbildungsbeginn)
                                                {{ $eintrag['azubi']->ausbildungsbeginn->format('d.m.Y') }}
                                                @if($eintrag['azubi']->ausbildungsende)
                                                    — {{ $eintrag['azubi']->ausbildungsende->format('d.m.Y') }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            @if($eintrag['letzterBericht'])
                                                KW {{ $eintrag['letzterBericht']->startdatum->format('W / Y') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($eintrag['letzterBericht'])
                                                @if($eintrag['letzterBericht']->signiert)
                                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">✅ Signiert</span>
                                                @elseif($eintrag['letzterBericht']->azubi_signiert)
                                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">⏳ Wartet auf dich</span>
                                                @elseif($eintrag['letzterBericht']->abgegeben)
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">📤 Abgegeben</span>
                                                @else
                                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">✏️ In Bearbeitung</span>
                                                @endif
                                                @if($eintrag['letzterBericht']->bearbeitet)
                                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs ml-1">⚠️ Bearbeitet</span>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">Keine Berichte</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 flex gap-2">
                                            <a href="{{ route('ausbilder.azubi.berichte', $eintrag['azubi']->id) }}"
                                                class="px-3 py-1 bg-blue-50 text-blue-600 rounded text-xs hover:bg-blue-100">
                                                Berichte
                                            </a>
                                            <form method="POST"
                                                action="{{ route('ausbilder.klasse.azubi.entfernen', [$klasse->id, $eintrag['azubi']->id]) }}"
                                                onsubmit="return confirm('Azubi aus der Klasse entfernen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="px-3 py-1 bg-red-50 text-red-600 rounded text-xs hover:bg-red-100">
                                                    Entfernen
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Noch keine Azubis in dieser Klasse.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
