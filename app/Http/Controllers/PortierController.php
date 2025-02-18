<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Entree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortierController extends Controller
{
    /**
     *  @OA\Schema(
     *     schema="Entrée",
     *     type="object",
     *     title="Entrée",
     *     description="Enregistrement d'une entrée par un portier",
     *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *     @OA\Property(property="ticket_id", type="string", format="uuid", example="98f6e0b6-7e88-4d4d-b731-d2dc8f6c29e1"),
     *     @OA\Property(property="portier_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *     @OA\Property(property="checkin_time", type="string", format="datetime", example="2025-01-21T12:00:00Z"),
     *     @OA\Property(property="created_at", type="string", format="datetime", example="2025-01-21T12:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="datetime", example="2025-01-21T12:00:00Z")
     * )
     * 
     * @OA\Post(
     *     path="/api/portier/entrée",
     *     summary="Enregistrer une entrée via QR code",
     *     tags={"Portier"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"qr_code"},
     *             @OA\Property(property="qr_code", type="string", example="QR123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Entrée enregistré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Entrée enregistré avec succès"),
     *             @OA\Property(property="check_in", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Ticket non disponible ou déjà utilisé"),
     *     @OA\Response(response=403, description="Interdit : Accès refusé")
     * )
     */
    public function checkIn(Request $request)
    {
        // Vérifier que l'utilisateur est un portier
        if (!Auth::user() || Auth::user()->role->name !== 'portier') {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $request->validate([
            'id' => 'required|uuid',
        ]);

        // Trouver le ticket correspondant à l'UUID fourni
        $ticket = Ticket::where('id', $request->id)->first();
        if (!$ticket) {
            return response()->json(['error' => 'Ticket non trouvé'], 404);
        }

        // Vérifier que le ticket est vendu et pas encore utilisé
        if ($ticket->status !== 'sold') {
            return response()->json(['error' => 'Ticket non disponible pour une entrée'], 400);
        }

        // Enregistrer l'entrée
        $checkIn = Entree::create([
            'ticket_id' => $ticket->id,
            'portier_id' => Auth::id(),
        ]);

        // Mettre à jour le ticket pour indiquer qu'il a été utilisé
        $ticket->update(['status' => 'used']);

        return response()->json([
            'message' => 'Entrée enregistrée avec succès',
            'check_in' => $checkIn
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/portier/total-entrées",
     *     summary="Liste des entrées effectuées par le portier",
     *     tags={"Portier"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des check-ins",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Entrée")
     *         )
     *     )
     * )
     */
    public function myCheckIns()
    {
        // Vérifier que l'utilisateur est un portier ou administrateur
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'portier'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $checkIns = Entree::with('ticket')->where('portier_id', Auth::id())->get();
        return response()->json($checkIns);
    }
}
