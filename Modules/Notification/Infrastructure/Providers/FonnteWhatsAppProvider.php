<?php 

namespace Modules\Notification\Infrastructure\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Contracts\WhatsAppProviderInterface;

class FonnteWhatsAppProvider implements WhatsAppProviderInterface
{
    private string $token;
    private string $endpoint = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = config('notification.fonnte.token');
    }

    public function sendMessage(string $target, string $message): bool
    {
        if (empty($this->token)) {
            Log::warning('Fonnte token is not set. Notification not sent.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->endpoint, [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ]);

            if (!$response->successful()) {
                Log::error('Fonnte API Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Fonnte Notification Exception: ' . $e->getMessage());
            return false;
        }
    }
}