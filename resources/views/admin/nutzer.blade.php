<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nutzerverwaltung</h2>
            <a href="{{ route('admin.einladungen') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                ✉️ Einladung senden
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded mb-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">E-Mail</th>
                            <th class="px-4 py-3 text-left">Rolle</th>
                            <th class="px-4 py-3 text-left">Klasse</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($nutzer as $user)
                            <tr class="hover:bg-gray-50 {{ !$user->aktiv ? 'opacity-50' : '' }}">
                                <td class="px-4 py-3 font-medium">{{ $user->full_name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs
                                        {{ $user->rolle === 'admin' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $user->rolle === 'ausbilder' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $user->rolle === 'azubi' ? 'bg-green-100 text-green-700' : '' }}">
                                        {{ ucfirst($user->rolle) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $user->klasse?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($user->aktiv)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Aktiv</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-xs">Deaktiviert</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($user->id !== auth()->id())
                                        <button onclick="editModal({{ $user->id }}, '{{ $user->rolle }}', {{ $user->aktiv ? 'true' : 'false' }}, {{ $user->klasse_id ?? 'null' }})"
                                            class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs hover:bg-gray-200">
                                            ✏️ Bearbeiten
                                        </button>
                                        <form method="POST" action="{{ route('admin.nutzer.loeschen', $user->id) }}"
                                            class="inline"
                                            onsubmit="return confirm('Nutzer wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs hover:bg-red-100">
                                                🗑️
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">— Du —</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Nutzer bearbeiten</h3>
            <form method="POST" id="edit-form">
                @csrf
                @method('PATCH')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                        <select name="rolle" id="edit-rolle" onchange="toggleKlasse()"
                            class="w-full border rounded px-3 py-2 text-sm">
                            <option value="azubi">Azubi</option>
                            <option value="ausbilder">Ausbilder</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div id="klasse-field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Klasse</label>
                        <select name="klasse_id" id="edit-klasse"
                            class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">— Keine —</option>
                            @foreach($klassen as $klasse)
                                <option value="{{ $klasse->id }}">{{ $klasse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="aktiv" id="edit-aktiv" value="1">
                        <label for="edit-aktiv" class="text-sm">Nutzer aktiv</label>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        Speichern
                    </button>
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm hover:bg-gray-200">
                        Abbrechen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editModal(id, rolle, aktiv, klasseId) {
        document.getElementById('edit-form').action = `/admin/nutzer/${id}`;
        document.getElementById('edit-rolle').value = rolle;
        document.getElementById('edit-aktiv').checked = aktiv;
        document.getElementById('edit-klasse').value = klasseId || '';
        toggleKlasse();
        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-modal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-modal').classList.remove('flex');
    }

    function toggleKlasse() {
        const rolle = document.getElementById('edit-rolle').value;
        document.getElementById('klasse-field').style.display = rolle === 'azubi' ? 'block' : 'none';
    }
    </script>

</x-app-layout>
