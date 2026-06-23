<?php
use Modules\Guest\Models\Guest;
use Modules\Guest\Transformers\AdminGuestResource;
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guest = Guest::first();
$guest->load(['schedule.tenant', 'schedule.room', 'bill']);
echo json_encode((new AdminGuestResource($guest))->resolve());
