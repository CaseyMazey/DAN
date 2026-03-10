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
                            <input type="text" value="{{ $user->abteilung ?? '—' }}"
                                class="border rounded px-3 py-2 w-full text-sm bg-gray-50" readonly>
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

        </div>
    </div>
</x-app-layout>