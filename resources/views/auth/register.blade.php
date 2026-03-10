<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Vorname -->
        <div>
            <x-input-label for="vorname" :value="__('Vorname')" />
            <x-text-input id="vorname" class="block mt-1 w-full" type="text" name="vorname" :value="old('vorname')" required autofocus />
            <x-input-error :messages="$errors->get('vorname')" class="mt-2" />
        </div>

        <!-- Nachname -->
        <div class="mt-4">
            <x-input-label for="nachname" :value="__('Nachname')" />
            <x-text-input id="nachname" class="block mt-1 w-full" type="text" name="nachname" :value="old('nachname')" required />
            <x-input-error :messages="$errors->get('nachname')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('E-Mail')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Passwort -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Passwort')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Passwort bestätigen -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Passwort bestätigen')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                Bereits registriert?
            </a>
            <x-primary-button class="ms-4">
                Registrieren
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>