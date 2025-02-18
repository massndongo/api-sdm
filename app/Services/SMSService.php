<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SMSService
{
    public function envoyerSMS($sender, $numeros, $message)
    {
        $data = [
            "from" => $sender,
            "to" => $numeros,
            "text" => $message,
        ];

        $response = Http::withHeaders([
            "accept" => "application/json",
            "authorization" => "Basic YXNzb25rbzpNbG91bWExNA==",
            "content-type" => "application/json",
        ])->withoutVerifying()->post("https://api.freebusiness.sn/sms/1/text/single", $data);

        if ($response->failed()) {
            return "Erreur d'envoi SMS : " . $response->body();
        }

        return $response->json();
    }
}