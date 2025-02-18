<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClubController extends Controller
{
    /**
     * @OA\Schema(
     *    schema="Club",
     *    type="object",
     *    title="Club",
     *    @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *    @OA\Property(property="name", type="string", example="AS Dakar"),
     *    @OA\Property(property="date_creation", type="string", format="date", example="2020-01-01"),
     *    @OA\Property(property="stade", type="string", example="Stade Léopold Sédar Senghor"),
     *    @OA\Property(property="entraineur", type="string", example="Jean Dupont"),
     *    @OA\Property(property="president", type="string", example="Pierre Ndiaye"),
     *    @OA\Property(property="user_id", type="string", format="uuid", example="f3f9c8c1-72ab-4a6b-9d74-3f4078ce7e84")
     * )
     *
     * @OA\Get(
     *     path="/api/clubs",
     *     summary="Lister tous les clubs",
     *     tags={"Clubs"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clubs récupérée avec succès",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Club"))
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function index()
    {
        $clubs = Club::with('user')->get();
        return response()->json($clubs);
    }

    /**
     * @OA\Get(
     *     path="/api/clubs/{id}",
     *     summary="Afficher les détails d'un club",
     *     tags={"Clubs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du club",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du club récupérés avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club non trouvé"
     *     )
     * )
     */
    public function show($id)
    {
        $club = Club::with('user')->where('id', $id)->firstOrFail();
        return response()->json($club);
    }

    /**
     * @OA\Post(
     *     path="/api/clubs",
     *     summary="Créer un nouveau club",
     *     tags={"Clubs"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="AS Dakar"),
     *             @OA\Property(property="date_creation", type="string", format="date", example="2020-01-01"),
     *             @OA\Property(property="stade", type="string", example="Stade Léopold Sédar Senghor"),
     *             @OA\Property(property="entraineur", type="string", example="Jean Dupont"),
     *             @OA\Property(property="president", type="string", example="Pierre Ndiaye"),
     *             @OA\Property(property="user_id", type="string", format="uuid", example="a2f9c8c1-72ab-4a6b-9d74-3f4078ce7e99"),
     *             @OA\Property(property="logo", type="string", format="binary", description="Logo du club")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Club créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:clubs,name',
                'date_creation' => 'nullable|date',
                'stade' => 'nullable|string|max:255',
                'entraineur' => 'nullable|string|max:255',
                'president' => 'nullable|string|max:255',
                'user_id' => 'nullable|string|exists:users,id',
                'logo' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048'
            ]);

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store('clubs', 'public');
            }

            $club = Club::create($validated);
            return response()->json(['message' => 'Club créé avec succès', 'club' => $club], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erreur Validation: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/clubs/{id}",
     *     summary="Mettre à jour un club existant",
     *     tags={"Clubs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du club",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="AS Dakar"),
     *             @OA\Property(property="date_creation", type="string", format="date", example="2020-01-01"),
     *             @OA\Property(property="stade", type="string", example="Stade Léopold Sédar Senghor"),
     *             @OA\Property(property="entraineur", type="string", example="Jean Dupont"),
     *             @OA\Property(property="president", type="string", example="Pierre Ndiaye"),
     *             @OA\Property(property="user_id", type="string", format="uuid"),
     *             @OA\Property(property="logo", type="string", format="binary", description="Logo du club")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Club")
     *     ),
     *     @OA\Response(response=404, description="Club non trouvé"),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $club = Club::where('id', $id)->firstOrFail();

            $validated = $request->validate([
                'name' => 'nullable|string|max:255|unique:clubs,name,' . $club->id,
                'date_creation' => 'nullable|date',
                'stade' => 'nullable|string|max:255',
                'entraineur' => 'nullable|string|max:255',
                'president' => 'nullable|string|max:255',
                'user_id' => 'nullable|string|exists:users,id',
                'logo' => 'nullable|image|mimes:jpg,png,jpeg,gif|max:2048',
            ]);

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            if ($request->hasFile('logo')) {
                $originalName = $request->file('logo')->getClientOriginalName();
                $extension = $request->file('logo')->getClientOriginalExtension();
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

                if ($club->logo) {
                    Storage::disk('public')->delete($club->logo);
                }

                $validated['logo'] = $request->file('logo')->storeAs('clubs', $safeName, 'public');
            }

            $club->update($validated);
            return response()->json(['message' => 'Club mis à jour avec succès', 'club' => $club]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erreur Validation: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/clubs/{id}",
     *     summary="Supprimer un club",
     *     tags={"Clubs"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du club",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Club supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club non trouvé"
     *     )
     * )
     */
    public function destroy($id)
    {
        $club = Club::where('id', $id)->firstOrFail();

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin','gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $club->delete();
        return response()->json(['message' => 'Club supprimé avec succès']);
    }
}
