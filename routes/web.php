<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/connexion', function () {
    return view('auth.login');  // Affiche la vue de connexion
})->name('login');

Route::get('/api-stadedembour', function () {
    if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
        return redirect(env('API_BASE_URL') . '/documentation');
    }
    return redirect('/connexion')->withErrors([
        'authorization' => 'Vous devez être administrateur pour accéder à cette page.',
    ]);
});

Route::post('/connexion', function (Request $request) {
    $request->validate([
        'phone' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt(['phone' => $request->phone, 'password' => $request->password])) {
         // Rediriger vers la documentation API si l'utilisateur est autorisé
        return redirect('/api-stadedembour');
    }

    // Ajoutez un message d'erreur plus détaillé
    return back()->withErrors([
        'phone' => 'Numéro de téléphone ou mot de passe incorrect.',
    ])->withInput($request->only('phone'));
})->name('seconnecter');
