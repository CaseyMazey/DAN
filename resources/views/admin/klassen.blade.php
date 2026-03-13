<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Klassenverwaltung</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
            @endif

            {{-- Neue Klasse --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Neue Klasse erstellen</h3>
                <form method="POST" action="{{ route('admin.klassen.erstellen') }}" class="flex gap-3">
                    @csrf
                    <input type="text" name="name" placeholder="Klassenname (z.B. IT-2024)"
                        class="border rounded px-3 py-2 text-sm flex-1" required>
                    <input type="text" name="jahrgang" placeholder="Jahrgang (z.B. 2024)"
                        class="border rounded px-3 py-2 text-sm w-36">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        + Erstellen
                    </button>
                </form>
            </div>

            {{-- Klassen Liste --}}
            @forelse($klassen as $klasse)
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-lg">{{ $klasse->name }}</h3>
                            @if($klasse->jahrgang)
                                <p class="text-sm text-gray-500">Jahrgang {{ $klasse->jahrgang }}</p>
                            @endif
                            <div class="flex gap-3 mt-1 text-xs text-gray-400">
                                <span>{{ $klasse->azubis->count() }} Azubis</span>
                                <span>{{ $klasse->ausbilder->count() }} Ausbilder</span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <span class="px-2 py-0.5 rounded text-xs {{ $klasse->aktiv ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $klasse->aktiv ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                            <button onclick="editKlasse({{ $klasse->id }}, '{{ $klasse->name }}', '{{ $klasse->jahrgang }}', {{ $klasse->aktiv ? 'true' : 'false' }})"
                                class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs hover:bg-gray-200">
                                ✏️ Bearbeiten
                            </button>
                            <form method="POST" action="{{ route('admin.klassen.loeschen', $klasse->id) }}"
                                class="inline"
                                onsubmit="return confirm('Klasse wirklich löschen?')">
                                @csrf
                                @method('DELETE')
                                <button class="px-2 py-1 bg-red-50 text-red-600 rounded text-xs hover:bg-red-100">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Ausbilder zuweisen --}}
                    <form method="POST" action="{{ route('admin.klassen.ausbilder', $klasse->id) }}" class="mb-4">
                        @csrf
                        @method('PATCH')
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Zugewiesene Ausbilder</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($ausbilder as $a)
                                <label class="flex items-center gap-1 text-sm">
                                    <input type="checkbox" name="ausbilder_ids[]" value="{{ $a->id }}"
                                        {{ $klasse->ausbilder->contains($a->id) ? 'checked' : '' }}>
                                    {{ $a->full_name }}
                                </label>
                            @endforeach
                        </div>
                        <button class="px-3 py-1 bg-blue-50 text-blue-600 rounded text-xs hover:bg-blue-100">
                            Speichern
                        </button>
                    </form>

                    {{-- Azubis --}}
                    @if($klasse->azubis->count())
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Azubis</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($klasse->azubis as $azubi)
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
                                    {{ $azubi->full_name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white shadow rounded-lg p-6">
                    <p class="text-sm text-gray-400">Noch keine Klassen vorhanden.</p>
                </div>
            @endforelse

        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Klasse bearbeiten</h3>
            <form method="POST" id="edit-form">
                @csrf
                @method('PATCH')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name" id="edit-name"
                            class="w-full border rounded px-3 py-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jahrgang</label>
                        <input type="text" name="jahrgang" id="edit-jahrgang"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="aktiv" id="edit-aktiv" value="1">
                        <label for="edit-aktiv" class="text-sm">Klasse aktiv</label>
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
    function editKlasse(id, name, jahrgang, aktiv) {
        document.getElementById('edit-form').action = `/admin/klassen/${id}`;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-jahrgang').value = jahrgang || '';
        document.getElementById('edit-aktiv').checked = aktiv;
        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-modal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-modal').classList.remove('flex');
    }
    </script>

</x-app-layout>
