<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einladung ungültig — DAN 2.0</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-lg shadow p-8 text-center">
        <div class="text-5xl mb-4">⚠️</div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Einladungslink ungültig</h1>
        <p class="text-sm text-gray-500 mb-6">
            Dieser Link ist abgelaufen oder wurde bereits verwendet.<br>
            Bitte wende dich an deinen Administrator.
        </p>
        <a href="{{ route('login') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
            Zur Anmeldung
        </a>
    </div>

</body>
</html>
