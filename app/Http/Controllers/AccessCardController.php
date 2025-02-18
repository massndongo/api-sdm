<?php

namespace App\Http\Controllers;

use App\Models\AccessCard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AccessCardController extends Controller
{
    /**
     * @OA\Get(
     *    path="/api/cartes",
     *    summary="Liste des cartes d'accès",
     *    tags={"Access Cards"},
     *    @OA\Response(response=200, description="Liste des cartes d'accès")
     * )
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $cards = AccessCard::with('user')->get();
        return response()->json($cards);
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/generate",
     *     summary="Générer une carte d'accès",
     *     tags={"Access Cards"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="string", format="uuid", example="f3f9c8c1-72ab-4a6b-9d74-3f4078ce7e84", description="UUID de l'utilisateur si déjà existant"),
     *             @OA\Property(property="firstname", type="string", example="John"),
     *             @OA\Property(property="lastname", type="string", example="Doe"),
     *             @OA\Property(property="role_id", type="string", format="uuid", example="a2f9c8c1-72ab-4a6b-9d74-3f4078ce7e99", description="UUID du rôle si on crée un nouvel utilisateur"),
     *             @OA\Property(property="prix", type="number", format="float", example=50.00),
     *             @OA\Property(property="phone", type="string", example="+221777000111")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Carte générée avec succès"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'firstname' => 'required_if:user_id,null',
            'lastname' => 'required_if:user_id,null',
            'role_id' => 'required_if:user_id,null|exists:roles,id',
            'prix' => 'required|numeric|min:0',
            'phone' => 'required_if:user_id,null',
        ]);

        // Génération de valeurs uniques
        $validated['qr_code'] = Str::random(16);
        $validated['card_number'] = Str::random(16);

        DB::beginTransaction();
        try {
            // Récupération ou création d'utilisateur
            if ($request->user_id) {
                // Si l'utilisateur existe déjà
                $user = User::where('id', $request->user_id)->firstOrFail();
            } else {
                // Création d'un nouvel utilisateur
                $user = User::create([
                    'firstname' => $validated['firstname'],
                    'lastname' => $validated['lastname'],
                    'role_id' => $validated['role_id'],  // ou un rôle "supporter"
                    'is_active' => true,
                    'phone' => $validated['phone'],
                    'password' => bcrypt('stade'),
                ]);
            }

            // Création de la carte d'accès
            $card = AccessCard::create([
                'id' => (string) Str::uuid(), // si le champ 'id' est un uuid
                'user_id' => $user->id,
                'qr_code' => $validated['qr_code'],
                'prix' => $validated['prix'],
                'card_number' => $validated['card_number'],
                'status' => 'active',
            ]);

            // Répertoire QR Code
            $qrCodeDirectory = storage_path('app/public/qrcodes');
            if (!File::exists($qrCodeDirectory)) {
                File::makeDirectory($qrCodeDirectory, 0755, true);
            }

            // Génération du QR Code
            $qrCodeRelativePath = 'qrcodes/card_' . $card->id . '.png';
            $qrCodePath = storage_path('app/public/' . $qrCodeRelativePath);

            // Par exemple, le lien vers la carte
            $url = url('/api/cartes/' . $card->id);

            QrCode::format('png')->size(200)->generate($url, $qrCodePath);

            // Mise à jour du QR code avec l'URL publique
            $card->update([
                'qr_code' => asset('storage/' . $qrCodeRelativePath)
            ]);

            DB::commit();
            return response()->json(['message' => 'Carte générée avec succès', 'card' => $card], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Suppression si nouvel utilisateur créé
            if (!isset($request->user_id) && isset($user)) {
                $user->delete();
            }
            return response()->json(['message' => 'Échec de la création de la carte', 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/block/{card}",
     *     summary="Bloquer une carte d'accès",
     *     tags={"Access Cards"},
     *     @OA\Parameter(
     *         name="card",
     *         in="path",
     *         required=true,
     *         description="UUID de la carte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Carte bloquée avec succès")
     * )
     */
    public function block($id)
    {
        $card = AccessCard::where('id', $id)->firstOrFail();
        $card->update(['status' => 'blocked']);
        return response()->json(['message' => 'Carte bloquée avec succès']);
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/deactivate/{card}",
     *     summary="Désactiver une carte d'accès",
     *     tags={"Access Cards"},
     *     @OA\Parameter(
     *         name="card",
     *         in="path",
     *         required=true,
     *         description="UUID de la carte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Carte désactivée avec succès")
     * )
     */
    public function desactivate($id)
    {
        $card = AccessCard::where('id', $id)->firstOrFail();
        $card->update(['status' => 'disabled']);
        return response()->json(['message' => 'Carte désactivée avec succès']);
    }

    /**
     * Réactiver une carte d'accès.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate($id)
    {
        $card = AccessCard::where('id', $id)->firstOrFail();
        $card->update(['status' => 'active']);
        return response()->json(['message' => 'Carte activée avec succès']);
    }

    /**
     * @OA\Get(
     *    path="/api/cartes/{id}",
     *    summary="Détails d'une carte d'accès",
     *    tags={"Access Cards"},
     *    @OA\Parameter(
     *        name="id",
     *        in="path",
     *        required=true,
     *        description="UUID de la carte",
     *        @OA\Schema(type="string", format="uuid")
     *    ),
     *    @OA\Response(response=200, description="Détails de la carte d'accès"),
     *    @OA\Response(response=404, description="Carte non trouvée")
     * )
     */
    public function show($id)
    {
        $card = AccessCard::with('user')->where('id', $id)->first();
        if (!$card) {
            return response()->json(['message' => 'Carte non trouvée'], 404);
        }
        return response()->json($card, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/sell/{id}",
     *     summary="Vendre une carte d'accès",
     *     tags={"Access Cards"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="UUID de la carte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Carte vendue avec succès")
     * )
     */
    public function sell($id)
    {
        $card = AccessCard::where('id', $id)->firstOrFail();
        if ($card->is_sold) {
            return response()->json(['message' => 'Cette carte a déjà été vendue'], 400);
        }

        $card->update(['is_sold' => true]);
        return response()->json(['message' => 'Carte vendue avec succès', 'card' => $card]);
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/stats",
     *     summary="Obtenir les statistiques des cartes vendues",
     *     tags={"Access Cards"},
     *     @OA\Response(response=200, description="Statistiques des cartes")
     * )
     */
    public function stats()
    {
        $totalCards = AccessCard::count();
        $soldCards = AccessCard::where('is_sold', true)->count();
        $availableCards = $totalCards - $soldCards;

        return response()->json([
            'total_cards' => $totalCards,
            'sold_cards' => $soldCards,
            'available_cards' => $availableCards
        ]);
    }
}
