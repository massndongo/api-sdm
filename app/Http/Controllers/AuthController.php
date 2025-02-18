<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\OTP;
use App\Models\Role;
use App\Models\User;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="API Stade de Mbour",
 *     version="1.0.0"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     operationId="register",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Créer un nouveau compte utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"firstname", "lastname", "phone", "password"},
     *             @OA\Property(property="firstname", type="string", example="Kirua"),
     *             @OA\Property(property="lastname", type="string", example="Zoldik"),
     *             @OA\Property(property="phone", type="string", example="+221772357546"),
     *             @OA\Property(property="password", type="string", example="passer123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur enregistré avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Utilisateur enregistré avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation."
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'phone'     => 'required|string|unique:users,phone',
            'password'  => 'required|string|min:6',
        ]);

        // Rôle par défaut : supporter (en UUID)
        $roleSupporter = Role::where('name', 'supporter')->first();

        // Création de l'utilisateur, compte inactif
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname'  => $request->lastname,
            'phone'     => $request->phone,
            'password'  => bcrypt($request->password),
            'is_active' => false,
            'role_id'   => $roleSupporter->id, // doit être un UUID
        ]);

        // Génération & envoi OTP
        $otp = rand(100000, 999999);
        OTP::create([
            'phone'      => $request->phone,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $message = "Bienvenue {$request->firstname} {$request->lastname} ! Votre code d'activation est : $otp. Valide pour 5 minutes.";
        $this->smsService->envoyerSMS("FEGGU", $request->phone, $message);

        return response()->json([
            'message' => 'Utilisateur enregistré avec succès. Veuillez activer votre compte avec le code envoyé.',
            'user'    => $user
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     operationId="login",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Connecter un utilisateur avec son téléphone et mot de passe",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "password"},
     *             @OA\Property(property="phone", type="string", example="+221772357546"),
     *             @OA\Property(property="password", type="string", example="passer123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie.",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLC..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides."
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Compte non activé."
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with('role')->where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Compte non activé. Veuillez vérifier votre téléphone.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-otp",
     *     operationId="verifyOtp",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Vérifier le code pour activer un compte",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "otp"},
     *             @OA\Property(property="phone", type="string", example="+221772357546"),
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte activé avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Compte activé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code invalide ou expiré."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé."
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
        ]);

        $otpRecord = OTP::where('otp', $request->otp)->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Code invalide.'], 400);
        }

        if (Carbon::now()->gt($otpRecord->expires_at)) {
            return response()->json(['message' => 'Code expiré.'], 400);
        }

        $user = User::where('phone', $otpRecord->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $user->is_active = true;
        $user->save();

        // $otpRecord->delete(); // Optionnel : supprimer l'OTP après usage

        return response()->json(['message' => 'Compte activé avec succès.']);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     operationId="logout",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Déconnecter l'utilisateur actuel",
     *     @OA\Response(
     *         response=200,
     *         description="L'utilisateur s'est déconnecté avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déconnecté avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé."
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    /**
     * @OA\Post(
     *     path="/api/resend-otp",
     *     operationId="resendOtp",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Renvoyer un nouveau code OTP en cas d'expiration",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+221772357546")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nouveau code OTP envoyé avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nouveau code OTP envoyé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé."
     *     )
     * )
     */
    public function resendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $otp = rand(100000, 999999);
        OTP::updateOrCreate(
            ['phone' => $request->phone],
            ['otp' => $otp, 'expires_at' => Carbon::now()->addMinutes(5)]
        );

        $message = "Votre nouveau code OTP est : $otp. Valide pour 5 minutes.";
        $this->smsService->envoyerSMS("FEGGU", $request->phone, $message);

        return response()->json(['message' => 'Nouveau code OTP envoyé avec succès.', 'user'=>$user]);
    }

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     operationId="forgotPassword",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Demander un code OTP pour réinitialiser le mot de passe",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+221772357546")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP envoyé avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé."
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $otp = rand(100000, 999999);
        OTP::updateOrCreate(
            ['phone' => $request->phone],
            ['otp' => $otp, 'expires_at' => Carbon::now()->addMinutes(5)]
        );

        $message = "Votre code de réinitialisation est : $otp. Valide pour 5 minutes.";
        $this->smsService->envoyerSMS("FEGGU", $request->phone, $message);

        return response()->json(['message' => 'Code OTP envoyé avec succès.', 'user' => $user]);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     operationId="resetPassword",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     summary="Réinitialiser le mot de passe avec un code OTP valide",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "otp", "new_password"},
     *             @OA\Property(property="phone", type="string", example="+221772357546"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="new_password", type="string", example="newpass123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe réinitialisé avec succès.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Mot de passe réinitialisé avec succès.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé."
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'        => 'required|string',
            'otp'          => 'required|numeric|digits:6',
            'new_password' => 'required|string|min:6',
        ]);

        $otpRecord = OTP::where([
            'phone' => $request->phone,
            'otp'   => $request->otp
        ])->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'Code OTP invalide.'], 400);
        }

        if (Carbon::now()->gt($otpRecord->expires_at)) {
            return response()->json(['message' => 'Le code OTP a expiré.'], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        // $otpRecord->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}