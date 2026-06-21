<?php

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Contracts\WhatsAppProviderInterface;

class FonnteWhatsAppProvider implements WhatsAppProviderInterface
{
    private string $token;

    private string $endpoint = 'https://api.fonnte.com/send';

    private ?string $lastError = null;

    public function __construct()
    {
        $this->token = config('notification.fonnte.token');
    }

    public function sendMessage(string $target, string $message): bool
    {
        $this->lastError = null;

        if (empty($this->token)) {
            $this->lastError = 'Fonnte token is not configured.';
            Log::warning('Fonnte token is not set. Notification not sent.');

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->endpoint, [
                'target'      => $target,
                'message'     => $message,
                'countryCode' => '62',
            ]);

            if (! $response->successful()) {
                $this->lastError = 'HTTP '.$response->status().': '.$response->body();
                Log::error('Fonnte API HTTP Error: '.$response->body());

                return false;
            }

            $body = $response->json();

            // Fonnte mengembalikan HTTP 200 bahkan saat gagal kirim.
            // Status asli ada di field "status" pada body JSON.
            if (isset($body['status']) && $body['status'] === false) {
                $reason = $body['reason'] ?? $body['message'] ?? json_encode($body);
                $this->lastError = $reason;
                Log::warning('Fonnte rejected message: '.$reason);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error('Fonnte Notification Exception: '.$e->getMessage());

            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
