<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Meine Wochenberichte
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Erfolgsmeldung --}}
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Neuen Bericht / aktuelle Woche --}}
            <div class="mb-6">
                <a href="{{ route('wochenbericht.aktuell') }}"
                   class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Aktuelle Woche öffnen
                </a>
            </div>

            {{-- Berichtsliste --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">KW</th>
                            <th class="px-6 py-3 text-left">Zeitraum</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($berichte as $bericht)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold">
                                    KW {{ $bericht->startdatum->format('W') }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $bericht->startdatum->format('d.m.Y') }}
                                    –
                                    {{ $bericht->enddatum->format('d.m.Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($bericht->gesperrt)
                                        <span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">Gesperrt</span>
                                    @elseif($bericht->signiert)
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Signiert</span>
                                    @elseif($bericht->abgegeben)
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Abgegeben</span>
                                    @else
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">In Bearbeitung</span>
                                    @endif

                                    @if($bericht->bearbeitet)
                                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs ml-1">Bearbeitet</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('wochenbericht.show', $bericht->id) }}"
                                       class="text-blue-600 hover:underline text-sm">
                                        Öffnen
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    Noch keine Wochenberichte vorhanden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Pagination --}}
                <div class="px-6 py-4">
                    {{ $berichte->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>