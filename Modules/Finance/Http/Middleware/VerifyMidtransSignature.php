<?php

namespace Modules\Finance\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyMidtransSignature
{
    public function handle(Request $request, Closure $next)
    {
        $payload = $request->all();

        if (!isset($payload['order_id']) || !isset($payload['signature_key'])) {
            return response()->json(['message' => 'Missing signature properties'], 400);
        }

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = config('finance.midtrans.server_key');

        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $payload['signature_key']) {
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        return $next($request);
    }
}
