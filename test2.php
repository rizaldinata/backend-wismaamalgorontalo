<?php
use Illuminate\Foundation\Testing\WithoutMiddleware;

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = Request::create('/api/admin/guests', 'GET');
$req->headers->set('Accept', 'application/json');
$req->setUserResolver(function() { return App\Models\User::first(); });

// Bypass middleware by running through the app directly but wait, 
// let's just dispatch the controller method directly to be 100% sure.
$controller = app(\Modules\Guest\Http\Controllers\AdminGuestController::class);
$response = $controller->index($req);

echo json_encode($response->getData(true));
