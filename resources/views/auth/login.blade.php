<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    @vite('resources/css/app.css')
</head>

<body class="bg-gradient-to-r from-gray-900 to-gray-800 text-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md bg-gray-900 p-8 rounded-xl shadow-lg text-center border border-gray-700 w-full">
        <img src="{{ url('images/logo.png') }}" alt="Logo de la société" class="mx-auto mb-4 w-24 h-24">
        <h2 class="text-3xl font-bold text-white mb-6">Connexion</h2>
        
        @if ($errors->any())
            <div class="text-red-500 text-sm mb-4">
                {{ $errors->first() }}
            </div>
        @endif
        
        <form action="{{ route('seconnecter') }}" method="POST" class="space-y-4">
            @csrf
            <div class="text-left">
                <label for="phone" class="block text-gray-300 text-sm mb-1">Numéro de Téléphone</label>
                <input type="tel" id="phone" name="phone" required autofocus
                    class="w-full px-4 py-2 bg-gray-800 text-gray-100 border border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            
            <div class="text-left">
                <label for="password" class="block text-gray-300 text-sm mb-1">Mot de Passe</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-2 bg-gray-800 text-gray-100 border border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            
            <button type="submit"
                class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition text-sm font-semibold">
                Se Connecter
            </button>
        </form>
    </div>
</body>

</html>