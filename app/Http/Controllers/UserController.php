<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="Utilisateur",
     *     type="object",
     *     title="Utilisateur",
     *     description="Représentation d'un utilisateur",
     *     @OA\Property(property="id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *     @OA\Property(property="firstname", type="string", example="John"),
     *     @OA\Property(property="lastname", type="string", example="Doe"),
     *     @OA\Property(property="phone", type="string", example="+221772222222"),
     *     @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *     @OA\Property(property="role_id", type="string", format="uuid", example="5f05f8f7-ecbd-4c72-a264-37bd22e2dfb7"),
     *     @OA\Property(property="is_active", type="boolean", example=true)
     * )
     *
     * @OA\Get(
     *     path="/api/utilisateurs",
     *     summary="Liste des utilisateurs",
     *     tags={"Utilisateur"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des utilisateurs",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Utilisateur")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        // Vérification des rôles autorisés
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $users = User::with('role')->latest()->get();
        return response()->json($users);
    }

    /**
     * @OA\Get(
     *     path="/api/utilisateurs/{id}",
     *     summary="Détails d'un utilisateur",
     *     tags={"Utilisateur"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'utilisateur",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'utilisateur",
     *         @OA\JsonContent(ref="#/components/schemas/Utilisateur")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * @OA\Get(
     *     path="/api/utilisateurs/me",
     *     summary="Récupérer les informations de l'utilisateur connecté",
     *     tags={"Utilisateur"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'utilisateur connecté",
     *         @OA\JsonContent(ref="#/components/schemas/Utilisateur")
     *     ),
     *     @OA\Response(response=401, description="Non autorisé")
     * )
     */
    public function me(Request $request)
    {
        // Renvoie l'utilisateur actuellement authentifié
        return response()->json($request->user());
    }

    /**
     * @OA\Post(
     *     path="/api/utilisateurs",
     *     summary="Créer un nouvel utilisateur",
     *     tags={"Utilisateur"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstname", type="string", example="John"),
     *             @OA\Property(property="lastname", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="772222222"),
     *             @OA\Property(property="password", type="string", example="securepassword123"),
     *             @OA\Property(property="role_id", type="string", format="uuid", example="5f05f8f7-ecbd-4c72-a264-37bd22e2dfb7")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Utilisateur")
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'phone' => 'required|string|unique:users,phone',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'is_active' => 'boolean'
            ]);

            // Vérifier si l'utilisateur connecté peut attribuer ce rôle
            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            // Restriction pour le gestionnaire_club
            $roleName = Role::find($request->role_id)->name ?? null;
            if (
                Auth::user()->role->name == 'gestionnaire_club' &&
                in_array($roleName, ['super_admin', 'admin', 'gestionnaire_ligue', 'gestionnaire_district', 'gestionnaire_club'])
            ) {
                return response()->json(['error' => 'Interdit : Vous ne pouvez pas attribuer ce rôle'], 403);
            }

            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'role_id' => $request->role_id,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json(['message' => 'Utilisateur ajouté avec succès', 'user' => $user], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error: ' . $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to add user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utilisateurs/{id}",
     *     summary="Mettre à jour un utilisateur",
     *     tags={"Utilisateur"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'utilisateur",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstname", type="string", example="Kirua"),
     *             @OA\Property(property="lastname", type="string", example="Zoldik"),
     *             @OA\Property(property="phone", type="string", example="+221772222222"),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="role_id", type="string", format="uuid", example="5f05f8f7-ecbd-4c72-a264-37bd22e2dfb7")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Utilisateur")
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = User::with('role')->find($id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $validated = $request->validate([
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|unique:users,phone,' . $id, // si vous voulez gérer l'unicité
            'role_id' => 'nullable|exists:roles,id'
        ]);

        if ($request->has('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * @OA\Delete(
     *    path="/api/utilisateurs/{id}",
     *    summary="Supprimer un utilisateur",
     *    tags={"Utilisateur"},
     *    security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID de l'utilisateur",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur supprimé avec succès"
     *     ),
     *     @OA\Response(response=404, description="Utilisateur non trouvé")
     * )
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        if (in_array($user->role->name, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Vous ne pouvez pas supprimer un administrateur ou un super admin'], 403);
        }

        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
