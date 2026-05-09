<?php

return [
    'name' => 'Finance',

    'midtrans' => [
        'enabled'          => env('MIDTRANS_ENABLED', true),
        'enabled_payments' => explode(',', env('MIDTRANS_ENABLED_PAYMENTS', 'qris,gopay,shopeepay,bca_va,bni_va,bri_va,echannel,permata_va,other_va,indomaret,alfamart,credit_card,akulaku,kredivo')),
        'merchant_id'      => env('MIDTRANS_MERCHANT_ID'),
        'client_key'       => env('MIDTRANS_CLIENT_KEY'),
        'server_key'       => env('MIDTRANS_SERVER_KEY'),
        'is_production'    => env('MIDTRANS_IS_PRODUCTION', false),
        'notification_url' => env('MIDTRANS_NOTIFICATION_URL', ''),
    ]
];
