<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sales/stats",
     *     summary="Obtenir des statistiques sur les ventes",
     *     description="Récupère les statistiques globales (total des ventes, revenu, nombre de tickets) ainsi que la répartition par événement et par catégorie de ticket. Filtre possible par date de début et date de fin.",
     *     tags={"Ventes"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Date de début du filtre (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Date de fin du filtre (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques de ventes récupérées avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_sales", type="integer", example=50, description="Nombre total de ventes payées"),
     *             @OA\Property(property="total_revenue", type="number", format="float", example=125000.50, description="Revenu total"),
     *             @OA\Property(property="total_tickets", type="integer", example=150, description="Nombre total de tickets vendus"),
     *             @OA\Property(
     *                 property="sales_by_event",
     *                 type="array",
     *                 description="Répartition des ventes par événement",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="event_id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *                     @OA\Property(property="total_sales", type="integer", example=30),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=60000),
     *                     @OA\Property(property="total_tickets", type="integer", example=60),
     *                     @OA\Property(property="sales_percentage", type="number", format="float", example=60.00),
     *                     @OA\Property(property="revenue_percentage", type="number", format="float", example=48.00),
     *                     @OA\Property(property="tickets_percentage", type="number", format="float", example=40.00),
     *                     @OA\Property(property="event", type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *                         @OA\Property(property="name", type="string", example="Événement A")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="sales_by_category",
     *                 type="array",
     *                 description="Répartition des ventes par catégorie de tickets",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="ticket_category_id", type="string", format="uuid", example="af2c5c2a-90e3-4b06-a354-482b647f1234"),
     *                     @OA\Property(property="total_sales", type="integer", example=20),
     *                     @OA\Property(property="total_revenue", type="number", format="float", example=65000),
     *                     @OA\Property(property="total_tickets", type="integer", example=40),
     *                     @OA\Property(property="sales_percentage", type="number", format="float", example=40.00),
     *                     @OA\Property(property="revenue_percentage", type="number", format="float", example=52.00),
     *                     @OA\Property(property="tickets_percentage", type="number", format="float", example=26.67),
     *                     @OA\Property(property="category", type="object",
     *                         @OA\Property(property="id", type="string", format="uuid", example="af2c5c2a-90e3-4b06-a354-482b647f1234"),
     *                         @OA\Property(property="name", type="string", example="VIP")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getSalesStats(Request $request)
    {
        // Filtrer uniquement les ventes payées
        $query = Sale::where('status', 'paid');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        // Calcul des statistiques globales
        $totalSales = $query->count();         // Nombre total de ventes
        $totalRevenue = $query->sum('amount'); // Revenu total
        $totalTickets = $query->sum('quantity'); // Nombre total de tickets vendus

        // Statistiques par événement
        $salesByEvent = $query->selectRaw('event_id, COUNT(*) as total_sales, SUM(amount) as total_revenue, SUM(quantity) as total_tickets')
            ->groupBy('event_id')
            ->with('event:id,name') // Charger les détails de l'événement
            ->get();

        // Ajout des pourcentages pour chaque événement
        $salesByEvent->transform(function ($event) use ($totalSales, $totalRevenue, $totalTickets) {
            $event->sales_percentage = $totalSales > 0 ? round(($event->total_sales / $totalSales) * 100, 2) : 0;
            $event->revenue_percentage = $totalRevenue > 0 ? round(($event->total_revenue / $totalRevenue) * 100, 2) : 0;
            $event->tickets_percentage = $totalTickets > 0 ? round(($event->total_tickets / $totalTickets) * 100, 2) : 0;
            return $event;
        });

        // Statistiques par catégorie de ticket
        $salesByCategory = $query->selectRaw('ticket_category_id, COUNT(*) as total_sales, SUM(amount) as total_revenue, SUM(quantity) as total_tickets')
            ->groupBy('ticket_category_id')
            ->with('category:id,name') // Charger les détails de la catégorie
            ->get();

        // Ajout des pourcentages pour chaque catégorie de ticket
        $salesByCategory->transform(function ($category) use ($totalSales, $totalRevenue, $totalTickets) {
            $category->sales_percentage = $totalSales > 0 ? round(($category->total_sales / $totalSales) * 100, 2) : 0;
            $category->revenue_percentage = $totalRevenue > 0 ? round(($category->total_revenue / $totalRevenue) * 100, 2) : 0;
            $category->tickets_percentage = $totalTickets > 0 ? round(($category->total_tickets / $totalTickets) * 100, 2) : 0;
            return $category;
        });

        return response()->json([
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_tickets' => $totalTickets,
            'sales_by_event' => $salesByEvent,
            'sales_by_category' => $salesByCategory,
        ]);
    }
}
