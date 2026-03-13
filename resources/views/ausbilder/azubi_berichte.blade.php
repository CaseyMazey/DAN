<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Berichte von {{ $azubi->full_name }}
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">← Dashboard</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Kalenderwoche</th>
                            <th class="px-4 py-3 text-left">Zeitraum</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($berichte as $bericht)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    KW {{ $bericht->startdatum->format('W / Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $bericht->startdatum->format('d.m.Y') }} – {{ $bericht->enddatum->format('d.m.Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if($bericht->gesperrt)
                                            <span class="px-2 py-0.5 bg-gray-200 text-gray-700 rounded text-xs">🔒 Gesperrt</span>
                                        @elseif($bericht->signiert)
                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">✅ Signiert</span>
                                        @elseif($bericht->azubi_signiert)
                                            <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">⏳ Wartet auf dich</span>
                                        @elseif($bericht->abgegeben)
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">📤 Abgegeben</span>
                                        @else
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">✏️ In Bearbeitung</span>
                                        @endif
                                        @if($bericht->bearbeitet)
                                            <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded text-xs">⚠️ Bearbeitet</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('ausbilder.bericht.show', $bericht->id) }}"
                                        class="px-3 py-1 bg-blue-50 text-blue-600 rounded text-xs hover:bg-blue-100">
                                        Ansehen
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                    Keine Berichte vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
