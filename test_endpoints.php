<?php
/**
 * Endpoint Matrix Testing Script
 * Tests all GET endpoints from the Flutter endpoint list against the running server.
 * Usage: php artisan tinker < test_endpoints.php
 */

use Illuminate\Support\Facades\Artisan;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;
use Modules\Schedule\Models\Schedule;
use Modules\Finance\Models\Invoice;
use Modules\Inventory\Models\Inventory;

// === SETUP ===
echo "\n=== ENDPOINT MATRIX TESTING ===\n";
echo "Date: " . now()->toDateTimeString() . "\n\n";

// Get or create admin user
$admin = User::role('admin')->first();
if (!$admin) {
    $admin = User::role('super-admin')->first();
}
if (!$admin) {
    echo "ERROR: No admin user found. Run seeders first.\n";
    exit(1);
}
$adminToken = $admin->createToken('test-admin')->plainTextToken;
echo "Admin: {$admin->name} (ID: {$admin->id})\n";

// Get or create resident user
$resident = User::role('resident')->first();
if (!$resident) {
    $resident = User::role('member')->first();
}
$residentToken = null;
if ($resident) {
    $residentToken = $resident->createToken('test-resident')->plainTextToken;
    echo "Resident: {$resident->name} (ID: {$resident->id})\n";
} else {
    echo "WARNING: No resident/member user found. Resident endpoints will be skipped.\n";
}

// Collect some IDs for parameterized endpoints
$roomId = Room::first()?->id ?? 0;
$scheduleId = Schedule::first()?->id ?? 0;
$invoiceId = Invoice::first()?->id ?? 0;
$inventoryId = Inventory::first()?->id ?? 0;

echo "Sample IDs - Room: {$roomId}, Schedule: {$scheduleId}, Invoice: {$invoiceId}, Inventory: {$inventoryId}\n\n";

// === TEST FUNCTION ===
function testEndpoint(string $method, string $uri, string $token, string $label = '', array $data = []): array {
    $app = app();
    
    try {
        $request = \Illuminate\Http\Request::create($uri, $method, $data);
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        $request->headers->set('Accept', 'application/json');
        
        $response = $app->handle($request);
        $statusCode = $response->getStatusCode();
        $content = json_decode($response->getContent(), true);
        
        $keys = [];
        if (is_array($content)) {
            $keys = array_keys($content);
        }
        
        $ok = $statusCode >= 200 && $statusCode < 500;
        
        return [
            'method' => $method,
            'uri' => $uri,
            'label' => $label,
            'status' => $statusCode,
            'ok' => $ok,
            'keys' => implode(', ', array_slice($keys, 0, 6)),
            'note' => $ok ? '' : 'ERROR: ' . ($content['message'] ?? 'Unknown'),
        ];
    } catch (\Throwable $e) {
        return [
            'method' => $method,
            'uri' => $uri,
            'label' => $label,
            'status' => 'EXC',
            'ok' => false,
            'keys' => '',
            'note' => 'EXCEPTION: ' . substr($e->getMessage(), 0, 80),
        ];
    }
}

$results = [];

// === AUTH ENDPOINTS (no auth required) ===
echo "--- Testing Auth endpoints ---\n";
$results[] = testEndpoint('POST', '/api/login', '', 'Auth:login (no creds)', ['email' => 'test@test.com', 'password' => 'wrong']);
$results[] = testEndpoint('GET', '/api/me', $adminToken, 'Auth:me (admin)');
$results[] = testEndpoint('GET', '/api/permissions', $adminToken, 'Auth:permissions');
$results[] = testEndpoint('PUT', '/api/profile', $adminToken, 'Auth:profile', ['name' => $admin->name]);

// === ADMIN AUTH ENDPOINTS ===
echo "--- Testing Admin Auth endpoints ---\n";
$results[] = testEndpoint('GET', '/api/admin/permissions', $adminToken, 'Admin:permissions.index');
$results[] = testEndpoint('GET', '/api/admin/roles', $adminToken, 'Admin:roles.index');
$results[] = testEndpoint('GET', '/api/admin/users', $adminToken, 'Admin:users.index');
if ($admin->id) {
    $results[] = testEndpoint('GET', '/api/admin/users/' . $admin->id, $adminToken, 'Admin:users.show');
}

// === RESIDENT ENDPOINTS ===
echo "--- Testing Resident endpoints ---\n";
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/resident/profile', $residentToken, 'Resident:profile');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/resident/profile','label'=>'Resident:profile','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}
$results[] = testEndpoint('GET', '/api/v1/admin/residents', $adminToken, 'Admin:residents.index');

// === ROOM ENDPOINTS ===
echo "--- Testing Room endpoints ---\n";
$results[] = testEndpoint('GET', '/api/rooms', $adminToken, 'Room:index');
if ($roomId) {
    $results[] = testEndpoint('GET', '/api/rooms/' . $roomId, $adminToken, 'Room:show');
}
$results[] = testEndpoint('GET', '/api/rooms-schedules', $adminToken, 'Room:schedules');

// === SCHEDULE ENDPOINTS ===
echo "--- Testing Schedule endpoints ---\n";
$results[] = testEndpoint('GET', '/api/v1/room-schedules', $adminToken, 'Schedule:index');
if ($scheduleId) {
    $results[] = testEndpoint('GET', '/api/v1/room-schedules/' . $scheduleId, $adminToken, 'Schedule:show');
}
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/v1/room-schedules/my', $residentToken, 'Schedule:my');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/v1/room-schedules/my','label'=>'Schedule:my','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}
if ($roomId) {
    $results[] = testEndpoint('GET', '/api/v1/room-schedules/kamar/' . $roomId, $adminToken, 'Schedule:byRoom');
}

// === FINANCE ENDPOINTS (cross-module: Finance<->Schedule) ===
echo "--- Testing Finance endpoints ---\n";
$results[] = testEndpoint('GET', '/api/finance/dashboard/kpi-summary', $adminToken, 'Finance:kpiSummary [X-MODULE]');
$results[] = testEndpoint('GET', '/api/finance/dashboard/revenue-chart', $adminToken, 'Finance:revenueChart [X-MODULE]');
$results[] = testEndpoint('GET', '/api/finance/dashboard/due-invoices', $adminToken, 'Finance:dueInvoices [X-MODULE]');
$results[] = testEndpoint('GET', '/api/finance/dashboard/pending-payments', $adminToken, 'Finance:pendingPayments [X-MODULE]');
$results[] = testEndpoint('GET', '/api/finance/expenses', $adminToken, 'Finance:expenses.index');
$results[] = testEndpoint('GET', '/api/finance/payments', $adminToken, 'Finance:payments.index');
$results[] = testEndpoint('GET', '/api/finance/invoices', $adminToken, 'Finance:invoices.index [X-MODULE]');
if ($invoiceId) {
    $results[] = testEndpoint('GET', '/api/finance/invoices/' . $invoiceId, $adminToken, 'Finance:invoices.show [X-MODULE]');
}
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/finance/me/summary', $residentToken, 'Finance:me.summary [X-MODULE]');
    $results[] = testEndpoint('GET', '/api/finance/me/invoices', $residentToken, 'Finance:me.invoices [X-MODULE]');
    $results[] = testEndpoint('GET', '/api/finance/me/payments', $residentToken, 'Finance:me.payments');
    $results[] = testEndpoint('GET', '/api/finance/me/fines', $residentToken, 'Finance:me.fines [X-MODULE]');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/finance/me/summary','label'=>'Finance:me.summary','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
    $results[] = ['method'=>'GET','uri'=>'/api/finance/me/invoices','label'=>'Finance:me.invoices','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
    $results[] = ['method'=>'GET','uri'=>'/api/finance/me/payments','label'=>'Finance:me.payments','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
    $results[] = ['method'=>'GET','uri'=>'/api/finance/me/fines','label'=>'Finance:me.fines','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}
$results[] = testEndpoint('GET', '/api/finance/payment-methods', $adminToken, 'Finance:paymentMethods');
$results[] = testEndpoint('GET', '/api/finance/refund-requests', $adminToken, 'Finance:refundRequests');

// === GUEST ENDPOINTS (cross-module: Guest<->Schedule) ===
echo "--- Testing Guest endpoints ---\n";
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/guests', $residentToken, 'Guest:index [X-MODULE]');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/guests','label'=>'Guest:index','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}
$results[] = testEndpoint('GET', '/api/admin/guests', $adminToken, 'Guest:admin.index [X-MODULE]');
$results[] = testEndpoint('GET', '/api/admin/guest-bills', $adminToken, 'Guest:admin.bills [X-MODULE]');

// === MAINTENANCE ENDPOINTS ===
echo "--- Testing Maintenance endpoints ---\n";
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/v1/damage-reports/my-reports', $residentToken, 'Maint:myReports');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/v1/damage-reports/my-reports','label'=>'Maint:myReports','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}
$results[] = testEndpoint('GET', '/api/v1/damage-reports/admin', $adminToken, 'Maint:admin.index');
$results[] = testEndpoint('GET', '/api/v1/schedules', $adminToken, 'Maint:schedules.index');

// === INVENTORY ENDPOINTS ===
echo "--- Testing Inventory endpoints ---\n";
$results[] = testEndpoint('GET', '/api/inventory', $adminToken, 'Inventory:index');
if ($inventoryId) {
    $results[] = testEndpoint('GET', '/api/inventory/' . $inventoryId, $adminToken, 'Inventory:show');
}

// === NOTIFICATION ENDPOINTS ===
echo "--- Testing Notification endpoints ---\n";
$results[] = testEndpoint('GET', '/api/notification/logs', $adminToken, 'Notif:logs.index');
$results[] = testEndpoint('GET', '/api/notification/logs/summary', $adminToken, 'Notif:logs.summary');
$results[] = testEndpoint('GET', '/api/notification/recipients', $adminToken, 'Notif:recipients');

// === SETTING ENDPOINTS ===
echo "--- Testing Setting endpoints ---\n";
$results[] = testEndpoint('GET', '/api/v1/settings/public', '', 'Setting:public (no auth)');
$results[] = testEndpoint('GET', '/api/v1/settings', $adminToken, 'Setting:index');
$results[] = testEndpoint('GET', '/api/v1/settings/bank-accounts', $adminToken, 'Setting:bankAccounts');
$results[] = testEndpoint('GET', '/api/v1/settings/bank-accounts/public', '', 'Setting:bankAccounts.public (no auth)');
$results[] = testEndpoint('GET', '/api/v1/settings/feature-toggles', $adminToken, 'Setting:featureToggles');
$results[] = testEndpoint('GET', '/api/v1/settings/midtrans-fees', $adminToken, 'Setting:midtransFees');
$results[] = testEndpoint('GET', '/api/v1/settings/payment-methods', $adminToken, 'Setting:paymentMethods');

// === DASHBOARD ENDPOINTS (cross-module: aggregates everything) ===
echo "--- Testing Dashboard endpoints ---\n";
$results[] = testEndpoint('GET', '/api/dashboard/admin', $adminToken, 'Dashboard:adminStats [X-MODULE]');
if ($residentToken) {
    $results[] = testEndpoint('GET', '/api/dashboard/resident', $residentToken, 'Dashboard:residentStats [X-MODULE]');
} else {
    $results[] = ['method'=>'GET','uri'=>'/api/dashboard/resident','label'=>'Dashboard:residentStats','status'=>'SKIP','ok'=>true,'keys'=>'','note'=>'No resident user'];
}

// === PRINT RESULTS ===
echo "\n\n=== ENDPOINT MATRIX RESULTS ===\n";
echo str_pad('Label', 42) . ' | ' . str_pad('Method', 6) . ' | ' . str_pad('URI', 52) . ' | ' . str_pad('Status', 6) . ' | OK? | Note' . "\n";
echo str_repeat('-', 170) . "\n";

$totalOk = 0;
$totalFail = 0;
$totalSkip = 0;
$crossModuleResults = [];

foreach ($results as $r) {
    $okStr = $r['ok'] ? '✅' : '❌';
    if ($r['status'] === 'SKIP') {
        $okStr = '⏭️';
        $totalSkip++;
    } elseif ($r['ok']) {
        $totalOk++;
    } else {
        $totalFail++;
    }
    
    echo str_pad($r['label'], 42) . ' | ' . str_pad($r['method'], 6) . ' | ' . str_pad($r['uri'], 52) . ' | ' . str_pad((string)$r['status'], 6) . ' | ' . $okStr . '  | ' . $r['note'] . "\n";
    
    if (str_contains($r['label'], '[X-MODULE]')) {
        $crossModuleResults[] = $r;
    }
}

echo str_repeat('-', 170) . "\n";
echo "TOTAL: {$totalOk} OK, {$totalFail} FAILED, {$totalSkip} SKIPPED\n";

echo "\n=== CROSS-MODULE ENDPOINTS (High Risk) ===\n";
foreach ($crossModuleResults as $r) {
    $okStr = $r['ok'] ? '✅' : '❌';
    echo "{$okStr} [{$r['status']}] {$r['label']} → {$r['uri']}\n";
}

// Cleanup tokens
$admin->tokens()->where('name', 'test-admin')->delete();
if ($resident) {
    $resident->tokens()->where('name', 'test-resident')->delete();
}

echo "\n=== DONE ===\n";
