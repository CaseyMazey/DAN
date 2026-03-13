<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Einladungen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Neue Einladung --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Neue Einladung senden</h3>
                <form method="POST" action="{{ route('admin.einladungen.senden') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                            <input type="email" name="email" required
                                class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                            <select name="rolle" id="rolle-select" onchange="toggleKlasse()"
                                class="w-full border rounded px-3 py-2 text-sm">
                                <option value="azubi">Azubi</option>
                                <option value="ausbilder">Ausbilder</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div id="klasse-field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Klasse</label>
                            <select name="klasse_id" class="w-full border rounded px-3 py-2 text-sm">
                                <option value="">— Keine —</option>
                                @foreach($klassen as $klasse)
                                    <option value="{{ $klasse->id }}">{{ $klasse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        ✉️ Einladung senden
                    </button>
                </form>
            </div>

            {{-- Einladungen Liste --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">E-Mail</th>
                            <th class="px-4 py-3 text-left">Rolle</th>
                            <th class="px-4 py-3 text-left">Klasse</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Läuft ab</th>
                            <th class="px-4 py-3 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($einladungen as $einladung)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $einladung->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs
                                        {{ $einladung->rolle === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $einladung->rolle === 'ausbilder' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $einladung->rolle === 'azubi' ? 'bg-green-100 text-green-700' : '' }}">
                                        {{ ucfirst($einladung->rolle) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $einladung->klasse?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($einladung->istGenutzt())
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">✓ Genutzt</span>
                                    @elseif($einladung->istAbgelaufen())
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs">Abgelaufen</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">Ausstehend</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $einladung->expires_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.einladungen.loeschen', $einladung->id) }}"
                                        class="inline"
                                        onsubmit="return confirm('Einladung löschen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs hover:bg-red-100">
                                            🗑️
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">
                                    Noch keine Einladungen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
    function toggleKlasse() {
        const rolle = document.getElementById('rolle-select').value;
        document.getElementById('klasse-field').style.display = rolle === 'azubi' ? 'block' : 'none';
    }
    // Beim Laden aufrufen
    toggleKlasse();
    </script>

</x-app-layout>
