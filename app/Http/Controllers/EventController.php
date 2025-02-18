<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="Événement",
     *     type="object",
     *     title="Événement",
     *     description="Event model",
     *     @OA\Property(property="id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *     @OA\Property(property="name", type="string", example="Tournoi régional"),
     *     @OA\Property(property="description", type="string", example="Un tournoi pour les équipes locales."),
     *     @OA\Property(property="date", type="string", format="date", example="2025-03-01"),
     *     @OA\Property(property="lieu", type="string", example="Stade Léopold Sédar Senghor"),
     *     @OA\Property(property="club_id", type="string", format="uuid", example="3ff6e0b6-7e88-4d4d-b731-d2dc8f6c29e1")
     * )
     * @OA\Get(
     *     path="/api/events",
     *     summary="Liste des événements",
     *     tags={"Événement"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des événements",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Événement")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $events = Event::with(['club','clubAway','tickets.ticketCategory','location'])->get();
        return response()->json($events);
    }

    /**
     * @OA\Post(
     *     path="/api/events",
     *     summary="Créer un nouvel événement",
     *     tags={"Événement"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tournoi régional"),
     *             @OA\Property(property="description", type="string", example="Un tournoi pour les équipes locales."),
     *             @OA\Property(property="date", type="string", format="date", example="2025-03-01"),
     *             @OA\Property(property="start_time", type="string", format="date_format:H:i", example="15:00"),
     *             @OA\Property(property="lieu", type="string", example="Stade Léopold Sédar Senghor"),
     *             @OA\Property(property="club_id", type="string", format="uuid", example="cf15f624-b90c-4b06-8a65-cfdd647f646c"),
     *             @OA\Property(property="club_away_id", type="string", format="uuid", example="b8d6a0b6-7e88-4d4d-b731-d2dc8f6c2911"),
     *             @OA\Property(property="location_id", type="string", format="uuid", example="e9c6d2b6-1e88-4a7d-b731-c2dc8f6c2982")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Événement créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Événement")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'club_id' => 'required|exists:clubs,id',
            'club_away_id' => 'required|exists:clubs,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date_format:H:i'
        ]);

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $event = Event::create($validated);
        return response()->json($event, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/events/{id}",
     *     summary="Détails d'un événement",
     *     tags={"Événement"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'événement",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'événement",
     *         @OA\JsonContent(ref="#/components/schemas/Événement")
     *     ),
     *     @OA\Response(response=404, description="Événement non trouvé")
     * )
     */
    public function show($id)
    {
        $event = Event::with(['club','clubAway','tickets.ticketCategory','location'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($event);
    }

    /**
     * @OA\Put(
     *     path="/api/events/{id}",
     *     summary="Mettre à jour un événement",
     *     tags={"Événement"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'événement",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tournoi modifié"),
     *             @OA\Property(property="description", type="string", example="Nouvelle description"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-03-10"),
     *             @OA\Property(property="lieu", type="string", example="Stade Modifié"),
     *             @OA\Property(property="club_id", type="string", format="uuid"),
     *             @OA\Property(property="club_away_id", type="string", format="uuid"),
     *             @OA\Property(property="location_id", type="string", format="uuid"),
     *             @OA\Property(property="start_time", type="string", format="date_format:H:i", example="16:30")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Événement mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Événement")
     *     ),
     *     @OA\Response(response=404, description="Événement non trouvé")
     * )
     */
    public function update(Request $request, $id)
    {
        $event = Event::where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
            'club_id' => 'nullable|exists:clubs,id',
            'club_away_id' => 'nullable|exists:clubs,id',
            'location_id' => 'nullable|exists:locations,id',
            'start_time' => 'nullable|date_format:H:i',
        ]);

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $event->update($validated);
        return response()->json($event);
    }

    /**
     * @OA\Delete(
     *     path="/api/events/{id}",
     *     summary="Supprimer un événement",
     *     tags={"Événement"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'événement",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Événement supprimé avec succès"
     *     ),
     *     @OA\Response(response=404, description="Événement non trouvé")
     * )
     */
    public function destroy($id)
    {
        $event = Event::where('id', $id)->firstOrFail();

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $event->delete();
        return response()->json(['message' => 'Événement supprimé avec succès']);
    }
}
