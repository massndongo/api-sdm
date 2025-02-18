<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->baseUrl = env('PAYTECH_BASE_URL', 'https://paytech.sn/api/');
        $this->apiKey = env('PAYTECH_API_KEY');
        $this->apiSecret = env('PAYTECH_API_SECRET');
    }

    /**
     * Créer un paiement PayTech.
     *
     * @param float $amount Montant à payer.
     * @param string $callbackUrl URL de retour après paiement.
     * @param string $customerPhone Téléphone de l'utilisateur.
     * @param string|null $customerEmail Email de l'utilisateur (optionnel).
     * @return string URL de paiement générée.
     */
    // public function createPayment(float $amount, string $callbackUrl, string $customerPhone, string $customerEmail = null)
    // {
    //     $data = [
    //         'item_name' => 'Achat de ticket',
    //         'item_price' => $amount,
    //         'currency' => 'XOF',
    //         'ref_command' => uniqid('TICKET_'),
    //         'ipn_url' => $callbackUrl, // URL de notification
    //         'success_url' => $callbackUrl, // URL de succès
    //         'cancel_url' => $callbackUrl, // URL d'annulation
    //         'custom_field' => [
    //             'customer_phone' => $customerPhone,
    //             'customer_email' => $customerEmail,
    //         ],
    //     ];

    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $this->apiKey,
    //         'Accept' => 'application/json',
    //     ])->post($this->baseUrl . 'payment/request-payment', $data, [
    //         "API_KEY: ". $this->apiKey,
    //         "API_SECRET: ". $this->apiSecret
    //     ]);

    //     if ($response->failed()) {
    //         throw new \Exception('Erreur lors de la création du paiement : ' . $response->body());
    //     }

    //     $responseData = $response->json();

    //     return $responseData['redirect_url'] ?? null;
    // }

    public function createPayment(
        float $amount,
        string $callbackUrl,
        string $notifyUrl,
        string $customerPhone,
        string $customerEmail = null,
        string $reference
    ) {
        $data = [
            'item_name' => 'Achat de ticket',
            'item_price' => $amount,
            'currency' => 'xof',
            'command_name' => $reference,
            'ref_command' => uniqid('TICKET'),
            'ipn_url' => $notifyUrl,
            'success_url' => $callbackUrl,
            'cancel_url' => $callbackUrl,
            'env' => 'test',
            'custom_field' => json_encode([
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail ?: 'client@mail.com'
            ]),
        ];
        $response = Http::withHeaders([
            'API_KEY' => $this->apiKey,
            'API_SECRET' => $this->apiSecret
        ])->post($this->baseUrl . 'payment/request-payment', $data);
        if ($response->failed()) {
            Log::error('PayTech Error', [
                'response' => $response->body(),
                'data_sent' => $data,
            ]);
            throw new \Exception('Erreur lors de la création du paiement : ' . $response->body());
        }
        $responseData = $response->json();

        if (!isset($responseData['redirect_url'])) {
            throw new \Exception('URL de paiement introuvable dans la réponse.');
        }

        return $responseData;
    }
}
