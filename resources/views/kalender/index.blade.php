<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mein Kalender</h2>
            <div class="flex gap-2">
                <button onclick="document.getElementById('modal-fach').classList.remove('hidden')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">
                    Fach verwalten
                </button>
                <button onclick="document.getElementById('modal-erstellen').classList.remove('hidden')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                    + Neuer Eintrag
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            {{-- Notenübersicht --}}
            @if($noten->count())
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-lg">Notenübersicht</h3>
                        @if($gesamtdurchschnitt)
                            <span class="text-sm text-gray-500">
                                Gesamtdurchschnitt:
                                <span class="font-bold text-lg ml-1">{{ $gesamtdurchschnitt }}</span>
                            </span>
                        @endif
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        @foreach($noten as $fach => $data)
                            <div class="border rounded-lg p-4">
                                <h4 class="font-medium text-sm mb-2">{{ $fach }}</h4>
                                <p class="text-2xl font-bold mb-2">Ø {{ $data['durchschnitt'] }}</p>
                                <div class="space-y-1">
                                    @foreach($data['eintraege'] as $eintrag)
                                        <div class="flex justify-between text-xs text-gray-500">
                                            <span>{{ ucfirst($eintrag->typ) }} — {{ $eintrag->datum->format('d.m.Y') }}</span>
                                            <span class="font-medium">{{ $eintrag->note }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Kommende Einträge --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Kommende Termine</h3>
                @forelse($kommend as $eintrag)
                    <div class="flex justify-between items-start p-3 border rounded-lg mb-2">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm">{{ $eintrag->fach }}</span>
                                <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded">{{ ucfirst($eintrag->typ) }}</span>
                            </div>
                            @if($eintrag->thema)
                                <p class="text-xs text-gray-500">{{ $eintrag->thema }}</p>
                            @endif
                            @if($eintrag->lernnotizen)
                                <p class="text-xs text-gray-400 mt-1">📝 {{ $eintrag->lernnotizen }}</p>
                            @endif
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm font-medium">{{ $eintrag->datum->format('d.m.Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $eintrag->datum->diffForHumans() }}</p>
                            <div class="flex gap-2 mt-2 justify-end">
                                <button onclick="bearbeitenModal({{ $eintrag->id }}, '{{ addslashes($eintrag->fach) }}', '{{ $eintrag->typ }}', '{{ $eintrag->datum->format('Y-m-d') }}', '{{ addslashes($eintrag->thema ?? '') }}', '{{ addslashes($eintrag->lernnotizen ?? '') }}', '{{ $eintrag->note ?? '' }}')"
                                    class="text-xs text-blue-500 hover:underline">Bearbeiten</button>
                                <form method="POST" action="{{ route('kalender.destroy', $eintrag->id) }}"
                                    onsubmit="return confirm('Eintrag löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-400 hover:underline">Löschen</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Keine kommenden Termine.</p>
                @endforelse
            </div>

            {{-- Vergangene Einträge --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Vergangene Termine</h3>
                @forelse($vergangen as $eintrag)
                    <div class="flex justify-between items-start p-3 border rounded-lg mb-2 bg-gray-50">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm">{{ $eintrag->fach }}</span>
                                <span class="text-xs px-2 py-0.5 bg-gray-200 text-gray-600 rounded">{{ ucfirst($eintrag->typ) }}</span>
                                @if($eintrag->note)
                                    <span class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded font-medium">Note: {{ $eintrag->note }}</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded">Keine Note</span>
                                @endif
                            </div>
                            @if($eintrag->thema)
                                <p class="text-xs text-gray-500">{{ $eintrag->thema }}</p>
                            @endif
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm text-gray-500">{{ $eintrag->datum->format('d.m.Y') }}</p>
                            <div class="flex gap-2 mt-2 justify-end">
                                <button onclick="bearbeitenModal({{ $eintrag->id }}, '{{ addslashes($eintrag->fach) }}', '{{ $eintrag->typ }}', '{{ $eintrag->datum->format('Y-m-d') }}', '{{ addslashes($eintrag->thema ?? '') }}', '{{ addslashes($eintrag->lernnotizen ?? '') }}', '{{ $eintrag->note ?? '' }}')"
                                    class="text-xs text-blue-500 hover:underline">Bearbeiten</button>
                                <form method="POST" action="{{ route('kalender.destroy', $eintrag->id) }}"
                                    onsubmit="return confirm('Eintrag löschen?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-400 hover:underline">Löschen</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Keine vergangenen Termine.</p>
                @endforelse
            </div>

        </div>
    </div>

    {{-- Modal: Fächer verwalten --}}
    <div id="modal-fach" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Fächer verwalten</h3>

            {{-- Neues Fach --}}
            <form id="form-fach" class="flex gap-2 mb-4">
                @csrf
                <input type="text" id="neues-fach" placeholder="Neues Fach..." 
                    class="border rounded px-3 py-2 text-sm flex-1" required>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Hinzufügen</button>
            </form>

            {{-- Vorhandene Fächer --}}
            <div class="space-y-2" id="faecher-liste">
                @forelse($faecher as $fach)
                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                        <span class="text-sm">{{ $fach->name }}</span>
                        <form method="POST" action="{{ route('kalender.fach.destroy', $fach->id) }}"
                            onsubmit="return confirm('Fach löschen?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-400 hover:underline">Löschen</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Noch keine Fächer angelegt.</p>
                @endforelse
            </div>

            <div class="flex justify-end mt-4">
                <button type="button" onclick="document.getElementById('modal-fach').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Schließen</button>
            </div>
        </div>
    </div>

    {{-- Modal: Neuer Eintrag --}}
    <div id="modal-erstellen" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Neuer Eintrag</h3>
            <form method="POST" action="{{ route('kalender.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fach</label>
                    @if($faecher->count())
                        <select name="fach" class="border rounded px-3 py-2 w-full text-sm" required>
                            <option value="">-- Fach wählen --</option>
                            @foreach($faecher as $fach)
                                <option value="{{ $fach->name }}">{{ $fach->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-xs text-gray-400">Bitte zuerst ein Fach anlegen.</p>
                        <input type="hidden" name="fach" value="Allgemein">
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                    <select name="typ" class="border rounded px-3 py-2 w-full text-sm">
                        <option value="test">Test</option>
                        <option value="klassenarbeit">Klassenarbeit</option>
                        <option value="hausaufgabe">Hausaufgabe</option>
                        <option value="projekt">Projekt</option>
                        <option value="sonstiges">Sonstiges</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                    <input type="date" name="datum" class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thema</label>
                    <input type="text" name="thema" class="border rounded px-3 py-2 w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lernnotizen</label>
                    <textarea name="lernnotizen" rows="3" class="border rounded px-3 py-2 w-full text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                    <input type="number" name="note" step="0.5" min="1" max="6"
                        placeholder="z.B. 1.5" class="border rounded px-3 py-2 w-full text-sm">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-erstellen').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Erstellen</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Bearbeiten --}}
    <div id="modal-bearbeiten" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="font-semibold text-lg mb-4">Eintrag bearbeiten</h3>
            <form method="POST" id="form-bearbeiten" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fach</label>
                    @if($faecher->count())
                        <select name="fach" id="edit-fach" class="border rounded px-3 py-2 w-full text-sm" required>
                            @foreach($faecher as $fach)
                                <option value="{{ $fach->name }}">{{ $fach->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="fach" id="edit-fach" class="border rounded px-3 py-2 w-full text-sm" required>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                    <select name="typ" id="edit-typ" class="border rounded px-3 py-2 w-full text-sm">
                        <option value="test">Test</option>
                        <option value="klassenarbeit">Klassenarbeit</option>
                        <option value="hausaufgabe">Hausaufgabe</option>
                        <option value="projekt">Projekt</option>
                        <option value="sonstiges">Sonstiges</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                    <input type="date" name="datum" id="edit-datum" class="border rounded px-3 py-2 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thema</label>
                    <input type="text" name="thema" id="edit-thema" class="border rounded px-3 py-2 w-full text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lernnotizen</label>
                    <textarea name="lernnotizen" id="edit-lernnotizen" rows="3" class="border rounded px-3 py-2 w-full text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                    <input type="number" name="note" id="edit-note" step="0.5" min="1" max="6"
                        class="border rounded px-3 py-2 w-full text-sm">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-bearbeiten').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm">Abbrechen</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function bearbeitenModal(id, fach, typ, datum, thema, lernnotizen, note) {
        const fachSelect = document.getElementById('edit-fach');
        if (fachSelect.tagName === 'SELECT') {
            fachSelect.value = fach;
        } else {
            fachSelect.value = fach;
        }
        document.getElementById('edit-typ').value = typ;
        document.getElementById('edit-datum').value = datum;
        document.getElementById('edit-thema').value = thema;
        document.getElementById('edit-lernnotizen').value = lernnotizen;
        document.getElementById('edit-note').value = note;
        document.getElementById('form-bearbeiten').action = '/kalender/' + id;
        document.getElementById('modal-bearbeiten').classList.remove('hidden');
    }
    document.getElementById('form-fach').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const input = document.getElementById('neues-fach');
      const name = input.value.trim();
      if (!name) return;

      fetch('{{ route('kalender.fach.store') }}', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ name: name })
      })
      .then(res => res.json())
      .then(data => {
          if (data.success) {
              // Fach zur Liste hinzufügen
              const liste = document.getElementById('faecher-liste');
              const div = document.createElement('div');
              div.className = 'flex justify-between items-center p-2 bg-gray-50 rounded';
              div.innerHTML = `<span class="text-sm">${name}</span>
                  <button onclick="fachLoeschen(${data.id}, this)" class="text-xs text-red-400 hover:underline">Löschen</button>`;
              liste.appendChild(div);

              // Dropdown aktualisieren
              ['fach-select-erstellen', 'fach-select-bearbeiten'].forEach(id => {
                  const sel = document.getElementById(id);
                  if (sel) {
                      const opt = document.createElement('option');
                      opt.value = name;
                      opt.textContent = name;
                      sel.appendChild(opt);
                  }
              });

              input.value = '';
              input.focus();
          }
      });
  });

  function fachLoeschen(id, btn) {
      if (!confirm('Fach löschen?')) return;
      fetch(`/kalender/fach/${id}`, {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ _method: 'DELETE' })
      }).then(() => {
          btn.closest('div').remove();
      });
  }
    </script>

</x-app-layout>