<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite('resources/css/app.css')
    <title>Erreur 403 - Accès Interdit</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body class="bg-gradient-to-r from-gray-900 to-gray-800 text-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-md bg-gray-900 p-8 rounded-xl shadow-lg text-center border border-gray-700">
        <img src="{{ url('images/logo.png') }}" alt="Logo de la société" class="mx-auto mb-4 w-24 h-24">
        <h1 class="text-red-500 text-4xl font-extrabold mb-4">Erreur 403</h1>
        <p class="text-gray-300 text-lg mb-6">
            Accès interdit. Vous tentez d'accéder à une ressource protégée.
        </p>
        <p class="text-gray-400 mb-6">
            Cette page est réservée aux utilisateurs autorisés et aux développeurs de l'écosystème STADE DE MBOUR.
        </p>
        <div class="bg-gray-800 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-200 mb-2">Instructions pour les Développeurs :</h3>
            <ul class="text-gray-400 text-sm space-y-2 text-left">
                <li>&#8226; Accès restreint aux détenteurs d'une clé API valide.</li>
                <li>&#8226; Contactez l'administrateur système pour obtenir une clé API.</li>
            </ul>
        </div>
        <div class="flex flex-col space-y-3">
            <a href="{{ route('seconnecter') }}" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm transition">Se Connecter</a>
            <a href="mailto:contact@stadedembour.com" class="text-blue-400 hover:text-blue-500 text-sm transition">Contact Support</a>
        </div>
    </div>
</body>

</html>
