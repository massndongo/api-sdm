<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Sale;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\PayTechService;
use App\Services\SMSService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\File;

class TicketController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }
    
    /**
     * @OA\Get(
     *     path="/api/tickets",
     *     summary="Liste des tickets",
     *     tags={"Ticket"},
     *     @OA\Response(response=200, description="Liste des tickets"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index()
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        // On suppose que 'event' et 'ticketCategory' sont bien les relations définies dans le modèle Ticket
        $tickets = Ticket::with(['event', 'ticketCategory'])->get();
        return response()->json($tickets);
    }

    /**
     * @OA\Get(
     *     path="/api/tickets/{id}",
     *     summary="Détails d'un ticket",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du ticket",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Détails du ticket"),
     *     @OA\Response(response=404, description="Ticket non trouvé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show($id)
    {
        // On suppose que le modèle Ticket est configuré pour un UUID
        $ticket = Ticket::with(['event', 'ticketCategory'])->findOrFail($id);
        return response()->json($ticket);
    }

    /**
     * @OA\Put(
     *     path="/api/tickets/{id}",
     *     summary="Mettre à jour un ticket",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du ticket",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="prix", type="number", format="float", example=2000.00),
     *             @OA\Property(property="status", type="string", example="sold")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Ticket mis à jour avec succès"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Ticket non trouvé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'prix' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:available,sold,used',
        ]);

        $ticket->update($validated);
        return response()->json($ticket);
    }

    /**
     * @OA\Delete(
     *     path="/api/tickets/{id}",
     *     summary="Supprimer un ticket",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID du ticket",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Ticket supprimé avec succès"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     @OA\Response(response=404, description="Ticket non trouvé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy($id)
    {
        if (!Auth::user() || !in_array(Auth::user()->role->name, ['admin', 'super_admin', 'gestionnaire_club'])) {
            return response()->json(['error' => 'Interdit : Accès refusé'], 403);
        }

        $ticket = Ticket::findOrFail($id);
        $ticket->delete();
        return response()->json(['message' => 'Ticket supprimé avec succès']);
    }

    /**
     * @OA\Post(
     *     path="/api/generate-tickets",
     *     summary="Générer plusieurs tickets avec QR codes",
     *     tags={"Ticket"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="ticket_category_id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *             @OA\Property(property="event_id", type="string", format="uuid", example="89ff624-b90c-4b06-8a65-cfdd647f6422"),
     *             @OA\Property(property="quantity", type="integer", example=10),
     *             @OA\Property(property="prix", type="number", format="float", example=1000.00),
     *             @OA\Property(property="channel", type="string", example="online"),
     *             @OA\Property(property="status", type="string", example="available")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tickets générés avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *                 @OA\Property(property="ticket_category_id", type="string", format="uuid"),
     *                 @OA\Property(property="event_id", type="string", format="uuid"),
     *                 @OA\Property(property="prix", type="number", format="float", example=1000.00),
     *                 @OA\Property(property="qr_code", type="string", example="https://.../storage/qrcodes/ticket_1.png")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation des données"),
     *     @OA\Response(response=403, description="Accès refusé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function generateTickets(Request $request)
    {
        $validated = $request->validate([
            'ticket_category_id' => 'required|exists:ticket_categories,id',
            'quantity' => 'required|integer|min:1',
            'event_id' => 'required|exists:events,id',
            'prix' => 'required|numeric|min:0',
            'channel' => 'required|string|in:print,online',
            'status' => 'nullable|string|in:available,sold,used',
        ]);

        // ATTENTION : Str::random(16) sera le même pour tous les tickets si on le génère hors de la boucle
        // Si vous voulez un QR code différent par ticket, générez-le dans la boucle
        $qrCodeDirectory = storage_path('app/public/qrcodes');
        if (!File::exists($qrCodeDirectory)) {
            File::makeDirectory($qrCodeDirectory, 0755, true);
        }

        $tickets = [];
        for ($i = 0; $i < $validated['quantity']; $i++) {
            // On crée un ticket avec un code random unique pour chacun
            $randomCode = Str::random(16);

            $ticket = Ticket::create([
                'ticket_category_id' => $validated['ticket_category_id'],
                'event_id' => $validated['event_id'],
                'prix' => $validated['prix'],
                'channel' => $validated['channel'],
                'qr_code' => $randomCode, // champ provisoire
                'status' => $validated['status'] ?? 'available',
            ]);

            // Génération du QR code
            $qrCodeRelativePath = 'qrcodes/ticket_' . $ticket->id . '.png';
            $qrCodePath = storage_path('app/public/' . $qrCodeRelativePath);

            $qrCodeData = [
                'id' => (string) $ticket->id,
            ];

            QrCode::format('png')
                ->size(200)
                ->generate(json_encode($qrCodeData), $qrCodePath);

            // Mise à jour du ticket avec l'URL publique du QR code
            $ticket->update([
                'qr_code' => asset('storage/' . $qrCodeRelativePath)
            ]);

            $tickets[] = $ticket;
        }

        return response()->json($tickets, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/purchase-ticket",
     *     summary="Achat de ticket",
     *     tags={"Ticket"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event_id", type="string", format="uuid", example="039ff624-b90c-4b06-8a65-cfdd647f646c"),
     *             @OA\Property(property="category_id", type="string", format="uuid", example="949ff624-b90c-4b06-8a65-cfdd647f6411"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", example="+221777000111"),
     *             @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *             @OA\Property(property="quantity", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Achat de ticket réussi"),
     *     @OA\Response(response=400, description="Pas assez de tickets disponibles"),
     *     @OA\Response(response=422, description="Erreur de validation des données")
     * )
     */
    public function purchaseTicket(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'event_id' => 'required|exists:events,id',
                'category_id' => 'required|exists:ticket_categories,id',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone' => 'required|string',
                'email' => 'nullable|email',
                'quantity' => 'required|integer|min:1',
            ]);

            // Récupération du rôle "supporter"
            $roleSupporter = Role::where('name', 'supporter')->first();
            // Vérifier si un utilisateur avec le même téléphone existe
            $user = User::where('phone', $validated['phone'])->first();

            if (!$user) {
                $user = User::create([
                    'firstname' => $validated['first_name'],
                    'lastname' => $validated['last_name'],
                    'role_id' => $roleSupporter->id,
                    'is_active' => true,
                    'phone' => $validated['phone'],
                    'password' => bcrypt('stade'),
                ]);
            }

            $category = TicketCategory::findOrFail($validated['category_id']);

            // Vérifier la disponibilité
            $availableTickets = Ticket::where('ticket_category_id', $category->id)
                ->where('status', 'available')
                ->take($validated['quantity'])
                ->get();

            if ($availableTickets->count() < $validated['quantity']) {
                return response()->json(['message' => 'Not enough tickets available'], 400);
            }

            // Création de la vente
            $sale = Sale::create([
                'event_id' => $validated['event_id'],
                'ticket_category_id' => $category->id,
                'user_id' => $user->id,
                'quantity' => $validated['quantity'],
                'amount' => $validated['quantity'] * $availableTickets[0]->prix,
                'status' => 'pending',
            ]);

            // Réserver les tickets
            foreach ($availableTickets as $ticket) {
                $ticket->update([
                    'status' => 'sold',
                    'sale_id' => $sale->id,
                ]);
            }

            DB::commit();
            return response()->json(['sale_id' => $sale->id]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Si l'utilisateur venait d'être créé dans ce flux, le supprimer pour rollback complet
            if (!isset($request->user_id) && isset($user)) {
                $user->delete();
            }

            return response()->json(['message' => 'Échec de la création de vente', 'error' => $e->getMessage()], 422);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/get-payment-url/{saleId}",
     *     summary="Obtenir l'URL de paiement PayTech pour une vente",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="saleId",
     *         in="path",
     *         description="UUID de la vente (Sale)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Renvoie l'URL de paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="payment_url", type="string", example="https://paytech.sn/redirect_url")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Vente non trouvée"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function getPaymentUrl($saleId)
    {
        // On suppose que saleId est un UUID => paramètre doc mis à jour
        $sale = Sale::with(['user'])->findOrFail($saleId);

        $payTechService = new PayTechService();
        $paymentUrl = $payTechService->createPayment(
            $sale->amount,
            'https://admin.stadedembour.com/payment-success/' . $sale->id,
            'https://admin.stadedembour.com/payment-failure/' . $sale->id,
            $sale->user->phone,
            $sale->email,
            'TICKET-' . $sale->id
        );

        $sale->update(['token_payment_paytech' => $paymentUrl['token']]);

        return response()->json(['payment_url' => $paymentUrl['redirect_url']]);
    }

    /**
     * @OA\Post(
     *     path="/api/payment/callback/{saleId}",
     *     summary="Callback PayTech après un paiement",
     *     description="Endpoint appelé par PayTech pour confirmer le statut du paiement d'une vente.",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="saleId",
     *         in="path",
     *         description="UUID de la vente (Sale)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="completed", description="Statut renvoyé par PayTech")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement traité avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment processed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Paiement non trouvé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function handleCallback(Request $request, $saleId)
    {
        $sale = Sale::with(['event', 'user'])->findOrFail($saleId);

        if (!$sale->token_payment_paytech) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        if ($request->status === 'completed') {
            $sale->update(['status' => 'paid']);
            Ticket::where('sale_id', $sale->id)->update(['status' => 'sold']);
            //Send SMS 
            $message = "Bravo, vous venez d'acheter {$sale->quantity} ticket(s) pour l'événement {$sale->event->name}. Pour voir vos tickets voici vos données de connexion identifiant: {$sale->user->phone}, mot de passe: stade";
            $this->smsService->envoyerSMS("FEGGU", $sale->user->phone, $message);
        } else {
            $sale->update([
                'status' => 'cancelled',
                'token_payment_paytech' => null,
                'amount' => 0
            ]);

            // Rendre les billets disponibles
            Ticket::where('sale_id', $sale->id)->update([
                'status' => 'available',
                'sale_id' => null,
            ]);

            return response()->json(['message' => 'Payment cancel']);
        }

        return response()->json(['message' => 'Payment processed successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/payment/notify/{saleId}",
     *     summary="Notification IPN PayTech",
     *     description="Endpoint appelé par PayTech pour notifier instantanément le statut d'une transaction (IPN).",
     *     tags={"Ticket"},
     *     @OA\Parameter(
     *         name="saleId",
     *         in="path",
     *         description="UUID de la vente (Sale)",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="completed", description="Statut renvoyé par PayTech")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification traitée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment processed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Paiement non trouvé"),
     *     security={{"sanctum": {}}}
     * )
     */
    public function handleNotify(Request $request, $saleId)
    {
        // Traitement IPN
        return $this->handleCallback($request, $saleId);
    }

    /**
     * @OA\Get(
     *     path="/api/events/{buyer_id}/tickets",
     *     summary="Récupérer les tickets vendus à l'utilisateur authentifié",
     *     tags={"Ticket"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des tickets vendus à l'utilisateur",
     *     )
     * )
     */
    public function getUserTickets(Request $request)
    {
        // On suppose que sale_id est un champ UUID => param doc mis à jour
        $tickets = Ticket::where('sale_id', $request->user()->id)
            ->where('status', 'sold')
            ->get();

        return response()->json($tickets);
    }
}
