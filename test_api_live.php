<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Modules\Auth\Models\User;
use GuzzleHttp\Client;

$admin = User::role('admin')->first() ?? User::role('super-admin')->first();
$resident = User::role('resident')->first();

if (!$admin) die("No admin found\n");
$adminToken = $admin->createToken('live-test-admin')->plainTextToken;

$residentToken = null;
if ($resident) {
    $residentToken = $resident->createToken('live-test-resident')->plainTextToken;
}

$client = new Client(['base_uri' => 'http://localhost:8000/', 'http_errors' => false]);

function testApi(Client $client, $method, $uri, $token = null) {
    $headers = ['Accept' => 'application/json'];
    if ($token) $headers['Authorization'] = 'Bearer ' . $token;
    
    $start = microtime(true);
    $response = $client->request($method, $uri, ['headers' => $headers]);
    $duration = round((microtime(true) - $start) * 1000, 2);
    
    $status = $response->getStatusCode();
    echo str_pad("[$method] $uri", 50) . " => Status: $status ($duration ms)\n";
    if ($status >= 500) {
        echo "ERROR RESPONSE: " . substr($response->getBody()->getContents(), 0, 500) . "\n";
    }
}

echo "=== STARTING LIVE API TEST (http://localhost:8000) ===\n";

$endpoints = [
    ['GET', 'api/permissions', null], // public or protected? wait, some are protected
    ['GET', 'api/v1/settings/public', null],
    ['GET', 'api/rooms', null],
    ['GET', 'api/dashboard/admin', $adminToken],
    ['GET', 'api/v1/admin/residents', $adminToken],
    ['GET', 'api/finance/dashboard/kpi-summary', $adminToken],
    ['GET', 'api/finance/dashboard/revenue-chart', $adminToken],
    ['GET', 'api/finance/expenses', $adminToken],
];

foreach ($endpoints as $ep) {
    testApi($client, $ep[0], $ep[1], $ep[2]);
}

if ($residentToken) {
    echo "\n--- Resident Endpoints ---\n";
    $residentEndpoints = [
        ['GET', 'api/dashboard/resident', $residentToken],
        ['GET', 'api/finance/me/summary', $residentToken],
        ['GET', 'api/finance/me/invoices', $residentToken],
        ['GET', 'api/guests', $residentToken],
    ];
    foreach ($residentEndpoints as $ep) {
        testApi($client, $ep[0], $ep[1], $ep[2]);
    }
}
echo "=== DONE ===\n";
