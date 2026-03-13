<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ausbilder-Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ausstehende Signaturen --}}
            @if($ausstehendSignaturen->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-4 flex items-center gap-2">
                        ✍️ Ausstehende Signaturen
                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">
                            {{ $ausstehendSignaturen->count() }}
                        </span>
                    </h3>
                    <div class="space-y-2">
                        @foreach($ausstehendSignaturen as $bericht)
                            <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                                <div>
                                    <p class="text-sm font-medium">{{ $bericht->azubi->full_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        KW {{ $bericht->startdatum->format('W / Y') }} —
                                        {{ $bericht->startdatum->format('d.m.Y') }} bis {{ $bericht->enddatum->format('d.m.Y') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($bericht->bearbeitet)
                                        <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">⚠️ Bearbeitet</span>
                                    @endif
                                    <a href="{{ route('ausbilder.bericht.show', $bericht->id) }}"
                                        class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
                                        Ansehen & Signieren
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Klassen Übersicht --}}
            @forelse($klassenUebersicht as $item)
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">
                            <a href="{{ route('ausbilder.klasse.show', $item['klasse']->id) }}" class="hover:text-blue-600">
                                {{ $item['klasse']->name }}
                            </a>
                            @if($item['klasse']->jahrgang)
                                <span class="text-sm text-gray-400 font-normal">Jahrgang {{ $item['klasse']->jahrgang }}</span>
                            @endif
                        </h3>
                        <span class="text-xs text-gray-400">{{ $item['azubis']->count() }} Azubis</span>
                    </div>

                    @if($item['azubis']->count())
                        <div class="overflow-hidden border rounded-lg">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-2 text-left">Azubi</th>
                                        <th class="px-4 py-2 text-left">Letzter Bericht</th>
                                        <th class="px-4 py-2 text-left">Status</th>
                                        <th class="px-4 py-2 text-left">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($item['azubis'] as $eintrag)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium">
                                                {{ $eintrag['azubi']->full_name }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">
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
                                            <td class="px-4 py-3">
                                                <a href="{{ route('ausbilder.azubi.berichte', $eintrag['azubi']->id) }}"
                                                    class="px-3 py-1 bg-blue-50 text-blue-600 rounded text-xs hover:bg-blue-100">
                                                    Alle Berichte
                                                </a>
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
            @empty
                <div class="bg-white shadow rounded-lg p-6">
                    <p class="text-sm text-gray-400">Du bist noch keiner Klasse zugewiesen.</p>
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
