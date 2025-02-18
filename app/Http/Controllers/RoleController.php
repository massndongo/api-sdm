<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @OA\Schema(
     *     schema="Role",
     *     type="object",
     *     title="Role",
     *     required={"id", "name"},
     *     @OA\Property(
     *         property="id",
     *         type="string",
     *         format="uuid",
     *         description="UUID of the role",
     *         example="f3f9c8c1-72ab-4a6b-9d74-3f4078ce7e84"
     *     ),
     *     @OA\Property(
     *         property="name",
     *         type="string",
     *         description="Name of the role",
     *         example="admin"
     *     )
     * )
     *
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get list of roles",
     *     tags={"Role"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of roles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Role")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $roles = Role::latest()->get();
        return response()->json($roles);
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Create a new role",
     *     tags={"Role"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Role created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles',
            ]);

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $role = Role::create([
                'name' => $request->name
            ]);

            return response()->json(['message' => 'Role crée avec succés', 'role' => $role], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error: ' . $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create role: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     summary="Get a specific role",
     *     tags={"Role"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the role",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specific role",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show($id)
    {
        $role = Role::where('id', $id)->firstOrFail();

        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        return response()->json($role);
    }

    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Update an existing role",
     *     tags={"Role"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the role to update",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Role")
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $role = Role::where('id', $id)->firstOrFail();

            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $id,
            ]);

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $role->name = $request->name;
            $role->save();

            return response()->json(['message' => 'Role mis à jour', 'role' => $role]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Role not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error: ' . $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update role: ' . $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete a role",
     *     tags={"Role"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the role to delete",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role supprimé"
     *     ),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy($id)
    {
        try {
            $role = Role::where('id', $id)->firstOrFail();

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin','super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $role->delete();
            return response()->json(['message' => 'Role supprimé'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Role not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete role: ' . $e->getMessage()], 500);
        }
    }
}