<?php

namespace App\Http\Controllers;

use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketCategoryController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="TicketCategory",
     *     type="object",
     *     title="Ticket Category",
     *     description="Représentation d'une catégorie de tickets",
     *     @OA\Property(property="id", type="string", format="uuid", description="UUID unique de la catégorie", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *     @OA\Property(property="name", type="string", description="Nom de la catégorie", example="VIP"),
     *     @OA\Property(property="created_at", type="string", format="datetime", description="Date de création", example="2025-01-20T10:00:00Z"),
     *     @OA\Property(property="updated_at", type="string", format="datetime", description="Date de mise à jour", example="2025-01-21T14:00:00Z"),
     * )
     *
     * @OA\Get(
     *     path="/api/ticket-categories",
     *     summary="Liste toutes les catégories de tickets",
     *     description="Récupère toutes les catégories de tickets.",
     *     tags={"Ticket Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des catégories",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/TicketCategory")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = TicketCategory::all();
        return response()->json($categories, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/ticket-categories",
     *     summary="Créer une nouvelle catégorie de tickets",
     *     description="Ajoute une catégorie de tickets. Seuls les administrateurs peuvent effectuer cette action.",
     *     tags={"Ticket Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Nom de la catégorie", example="VIP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Catégorie créée",
     *         @OA\JsonContent(ref="#/components/schemas/TicketCategory")
     *     ),
     *     @OA\Response(response=403, description="Interdit : Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = TicketCategory::create($validated);
        return response()->json($category, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/ticket-categories/{id}",
     *     summary="Afficher une catégorie de tickets spécifique",
     *     description="Récupère une catégorie de tickets par son UUID.",
     *     tags={"Ticket Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la catégorie",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Catégorie trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/TicketCategory")
     *     ),
     *     @OA\Response(response=404, description="Catégorie non trouvée")
     * )
     */
    public function show($id)
    {
        $category = TicketCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }
        return response()->json($category, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/ticket-categories/{id}",
     *     summary="Mettre à jour une catégorie de tickets",
     *     description="Met à jour les informations d'une catégorie de tickets. Seuls les administrateurs peuvent effectuer cette action.",
     *     tags={"Ticket Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la catégorie",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Nom de la catégorie", example="Standard")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Catégorie mise à jour",
     *         @OA\JsonContent(ref="#/components/schemas/TicketCategory")
     *     ),
     *     @OA\Response(response=403, description="Interdit : Accès refusé"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")
     * )
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $category = TicketCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($validated);
        return response()->json($category, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/ticket-categories/{id}",
     *     summary="Supprimer une catégorie de tickets",
     *     description="Supprime une catégorie de tickets. Seuls les administrateurs peuvent effectuer cette action.",
     *     tags={"Ticket Categories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de la catégorie",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Catégorie supprimée avec succès"
     *     ),
     *     @OA\Response(response=403, description="Interdit : Accès refusé"),
     *     @OA\Response(response=404, description="Catégorie non trouvée")
     * )
     */
    public function destroy($id)
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $category = TicketCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Catégorie non trouvée'], 404);
        }

        $category->delete();
        return response()->json(null, 204);
    }
}
