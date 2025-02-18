<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    /**
     * @OA\Schema(
     *      schema="Location",
     *      type="object",
     *      title="Location",
     *      @OA\Property(property="id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *      @OA\Property(property="name", type="string", example="Stade Léopold Sédar Senghor"),
     *      @OA\Property(property="address", type="string", example="Avenue Nelson Mandela"),
     *      @OA\Property(property="city", type="string", example="Dakar"),
     * )
     * @OA\Get(
     *      path="/api/locations",
     *      summary="List of locations",
     *      tags={"Location"},
     *      security={{"sanctum": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="List of locations",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/Location")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        $locations = Location::latest()->get();
        return response()->json($locations);
    }

    /**
     * @OA\Post(
     *    path="/api/locations",
     *    summary="Create a new location",
     *    tags={"Location"},
     *    security={{"sanctum": {}}},
     *    @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *          @OA\Property(property="name", type="string", example="Stade Léopold Sédar Senghor"),
     *          @OA\Property(property="address", type="string", example="Avenue Nelson Mandela"),
     *          @OA\Property(property="city", type="string", example="Dakar")
     *       )
     *    ),
     *    @OA\Response(response=201, description="Location created successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/Location"
     *         )
     *    ),
     *    @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        try {
            // Vérifiez si l'utilisateur authentifié peut créer un lieu
            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $request->validate([
                'name' => 'required|string',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
            ]);

            $location = Location::create([
                'name' => $request->name,
                'address' => $request->address,
                'city' => $request->city,
            ]);

            return response()->json(['message' => 'Lieu créé avec succès', 'lieu' => $location], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/locations/{id}",
     *     summary="Location details",
     *     tags={"Location"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the location",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Location details",
     *         @OA\JsonContent(ref="#/components/schemas/Location")
     *     ),
     *     @OA\Response(response=404, description="Location not found")
     * )
     */
    public function show($id)
    {
        $location = Location::where('id', $id)->firstOrFail();
        return response()->json($location);
    }

    /**
     * @OA\Put(
     *    path="/api/locations/{id}",
     *    summary="Update an existing location",
     *    tags={"Location"},
     *    security={{"sanctum": {}}},
     *    @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the location",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *    ),
     *    @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Stade Léopold Sédar Senghor"),
     *             @OA\Property(property="address", type="string", example="Avenue Nelson Mandela"),
     *             @OA\Property(property="city", type="string", example="Dakar")
     *         )
     *    ),
     *    @OA\Response(response=200, description="Location updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Location")
     *    ),
     *    @OA\Response(response=404, description="Location not found")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $location = Location::where('id', $id)->firstOrFail();

            $validated = $request->validate([
                'name' => 'nullable|string|max:255|unique:locations,name,' . $location->id,
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
            ]);

            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $location->update($validated);
            return response()->json(['message' => 'Lieu mis à jour avec succès', 'lieu' => $location]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Location not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation Error: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/locations/{id}",
     *     summary="Delete a location",
     *     tags={"Location"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the location",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Lieu supprimé avec succès"),
     *     @OA\Response(response=404, description="Location not found")
     * )
     */
    public function destroy($id)
    {
        try {
            if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin'])) {
                return response()->json(['error' => 'Interdit : Accès refusé'], 403);
            }

            $location = Location::where('id', $id)->firstOrFail();
            $location->delete();

            return response()->json(['message' => 'Lieu supprimé avec succès', 'lieu' => $location]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Location not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
