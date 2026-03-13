<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung — DAN 2.0</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-lg shadow p-8">

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">DAN 2.0</h1>
            <p class="text-sm text-gray-500 mt-1">Konto erstellen</p>
        </div>

        <div class="mb-6 p-3 bg-blue-50 border border-blue-100 rounded text-sm text-blue-700">
            Du wurdest als <strong>{{ ucfirst($einladung->rolle) }}</strong> eingeladen
            @if($einladung->klasse)
                in Klasse <strong>{{ $einladung->klasse->name }}</strong>
            @endif
            — Konto für <strong>{{ $einladung->email }}</strong>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.invite.submit', $token) }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vorname</label>
                    <input type="text" name="vorname" value="{{ old('vorname') }}" required
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nachname</label>
                    <input type="text" name="nachname" value="{{ old('nachname') }}" required
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passwort</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passwort bestätigen</label>
                <input type="password" name="password_confirmation" required
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            @if($einladung->rolle === 'azubi')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ausbildungsbeginn</label>
                        <input type="date" name="ausbildungsbeginn" value="{{ old('ausbildungsbeginn') }}"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ausbildungsende</label>
                        <input type="date" name="ausbildungsende" value="{{ old('ausbildungsende') }}"
                            class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            @endif

            <button type="submit"
                class="w-full py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700">
                Konto erstellen
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-4">
            Bereits ein Konto? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Anmelden</a>
        </p>
    </div>

</body>
</html>
