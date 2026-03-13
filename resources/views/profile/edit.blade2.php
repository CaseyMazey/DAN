<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mein Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Erfolgsmeldung --}}
            @if(session('status') === 'profile-updated')
                <div class="p-4 bg-green-100 text-green-800 rounded">Profil gespeichert!</div>
            @endif
            @if(session('status') === 'password-updated')
                <div class="p-4 bg-green-100 text-green-800 rounded">Passwort geändert!</div>
            @endif

            {{-- Persönliche Daten --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Persönliche Daten</h3>
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vorname</label>
                            <input type="text" name="vorname" value="{{ old('vorname', $user->vorname) }}"
                                class="border rounded px-3 py-2 w-full text-sm" required>
                            @error('vorname')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nachname</label>
                            <input type="text" name="nachname" value="{{ old('nachname', $user->nachname) }}"
                                class="border rounded px-3 py-2 w-full text-sm" required>
                            @error('nachname')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-Mail</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                            class="border rounded px-3 py-2 w-full text-sm" required>
                        @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ausbildungsbeginn</label>
                            <input type="date" name="ausbildungsbeginn"
                                value="{{ old('ausbildungsbeginn', $user->ausbildungsbeginn?->format('Y-m-d')) }}"
                                class="border rounded px-3 py-2 w-full text-sm">
                            @error('ausbildungsbeginn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ausbildungsende</label>
                            <input type="date" name="ausbildungsende"
                                value="{{ old('ausbildungsende', $user->ausbildungsende?->format('Y-m-d')) }}"
                                class="border rounded px-3 py-2 w-full text-sm">
                            @error('ausbildungsende')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Nur lesbar --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                            <input type="text" value="{{ ucfirst($user->rolle) }}"
                                class="border rounded px-3 py-2 w-full text-sm bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Abteilung</label>
                            <input type="text" name="abteilung" value="{{ old('abteilung', $user->abteilung) }}"
                                class="border rounded px-3 py-2 w-full text-sm">
                            @error('abteilung')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        Speichern
                    </button>
                </form>
            </div>

            {{-- Passwort ändern --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Passwort ändern</h3>
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Aktuelles Passwort</label>
                        <input type="password" name="current_password"
                            class="border rounded px-3 py-2 w-full text-sm" required>
                        @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Neues Passwort</label>
                        <input type="password" name="password"
                            class="border rounded px-3 py-2 w-full text-sm" required>
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Passwort bestätigen</label>
                        <input type="password" name="password_confirmation"
                            class="border rounded px-3 py-2 w-full text-sm" required>
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        Passwort ändern
                    </button>
                </form>
            </div>

            {{-- Dark Mode --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Einstellungen</h3>
                <form method="POST" action="{{ route('profile.darkmode') }}" class="flex items-center gap-4">
                    @csrf
                    @method('PATCH')

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="dark_mode" value="1"
                            {{ $user->dark_mode ? 'checked' : '' }}
                            class="w-4 h-4">
                        <span class="text-sm font-medium text-gray-700">Dark Mode aktivieren</span>
                    </label>

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        Speichern
                    </button>
                </form>
            </div>

            {{-- Signatur --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="font-semibold text-lg mb-4">Meine Signatur</h3>

                @if($user->signatur_pfad)
                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-2">Aktuelle Signatur:</p>
                        <img src="{{ asset('storage/' . $user->signatur_pfad) }}"
                            class="border rounded max-h-24 bg-white">
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.signatur') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Canvas zeichnen --}}
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">Signatur zeichnen:</p>
                        <canvas id="signatur-canvas"
                            width="500" height="150"
                            class="border rounded bg-white cursor-crosshair w-full"
                            style="touch-action: none;">
                        </canvas>
                        <input type="hidden" name="signatur_canvas" id="signatur-canvas-data">
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="canvasLeeren()"
                                class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-sm">
                                🗑️ Leeren
                            </button>
                            <button type="button" onclick="canvasSpeichern()"
                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm">
                                ✅ Zeichnung verwenden
                            </button>
                        </div>
                    </div>

                    <p class="text-sm text-gray-400 text-center">— oder —</p>

                    {{-- Bild hochladen --}}
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-2">Signatur hochladen:</p>
                        <input type="file" name="signatur_bild" accept="image/*"
                            class="border rounded px-3 py-2 w-full text-sm">
                    </div>

                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                        Signatur speichern
                    </button>
                </form>
            </div>

            <script>
            const canvas = document.getElementById('signatur-canvas');
            const ctx = canvas.getContext('2d');
            let zeichnen = false;

            canvas.addEventListener('mousedown', e => { zeichnen = true; ctx.beginPath(); });
            canvas.addEventListener('mouseup', () => zeichnen = false);
            canvas.addEventListener('mouseleave', () => zeichnen = false);
            canvas.addEventListener('mousemove', e => {
                if (!zeichnen) return;
                const r = canvas.getBoundingClientRect();
                const scaleX = canvas.width / r.width;
                const scaleY = canvas.height / r.height;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#000';
                ctx.lineTo((e.clientX - r.left) * scaleX, (e.clientY - r.top) * scaleY);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo((e.clientX - r.left) * scaleX, (e.clientY - r.top) * scaleY);
            });

            // Touch support
            canvas.addEventListener('touchstart', e => {
                e.preventDefault();
                zeichnen = true;
                ctx.beginPath();
            });
            canvas.addEventListener('touchend', () => zeichnen = false);
            canvas.addEventListener('touchmove', e => {
                e.preventDefault();
                if (!zeichnen) return;
                const r = canvas.getBoundingClientRect();
                const scaleX = canvas.width / r.width;
                const scaleY = canvas.height / r.height;
                const touch = e.touches[0];
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#000';
                ctx.lineTo((touch.clientX - r.left) * scaleX, (touch.clientY - r.top) * scaleY);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo((touch.clientX - r.left) * scaleX, (touch.clientY - r.top) * scaleY);
            });

            function canvasLeeren() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                document.getElementById('signatur-canvas-data').value = '';
            }

            function canvasSpeichern() {
                document.getElementById('signatur-canvas-data').value = canvas.toDataURL('image/png');
                alert('Zeichnung wird beim Speichern übernommen!');
            }
            </script>

        </div>
    </div>
</x-app-layout>