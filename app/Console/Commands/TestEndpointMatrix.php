<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;
use Modules\Schedule\Models\Schedule;
use Modules\Finance\Models\Invoice;
use Modules\Inventory\Models\Inventory;

class TestEndpointMatrix extends Command
{
    protected $signature = 'test:endpoints';
    protected $description = 'Test all API endpoints for regression check';

    private array $results = [];

    public function handle(): int
    {
        $this->info("\n=== ENDPOINT MATRIX TESTING ===");
        $this->info("Date: " . now()->toDateTimeString());

        // Get admin user
        $admin = User::role('admin')->first() ?? User::role('super-admin')->first();
        if (! $admin) {
            $this->error("No admin user found.");
            return 1;
        }
        $adminToken = $admin->createToken('test-admin')->plainTextToken;
        $this->info("Admin: {$admin->name} (ID: {$admin->id})");

        // Get resident user
        $resident = User::role('resident')->first() ?? User::role('member')->first();
        $residentToken = null;
        if ($resident) {
            $residentToken = $resident->createToken('test-resident')->plainTextToken;
            $this->info("Resident: {$resident->name} (ID: {$resident->id})");
        } else {
            $this->warn("No resident/member user found. Resident endpoints will be skipped.");
        }

        // Sample IDs
        $roomId = Room::first()?->id ?? 0;
        $scheduleId = Schedule::first()?->id ?? 0;
        $invoiceId = Invoice::first()?->id ?? 0;
        $inventoryId = Inventory::first()?->id ?? 0;
        $this->info("Sample IDs - Room: {$roomId}, Schedule: {$scheduleId}, Invoice: {$invoiceId}, Inventory: {$inventoryId}");

        // === AUTH ===
        $this->testGet('/api/me', $adminToken, 'Auth:me');
        $this->testGet('/api/permissions', $adminToken, 'Auth:permissions');

        // === ADMIN AUTH ===
        $this->testGet('/api/admin/permissions', $adminToken, 'Admin:permissions');
        $this->testGet('/api/admin/roles', $adminToken, 'Admin:roles');
        $this->testGet('/api/admin/users', $adminToken, 'Admin:users.index');
        if ($admin->id) {
            $this->testGet('/api/admin/users/'.$admin->id, $adminToken, 'Admin:users.show');
        }

        // === RESIDENT ===
        if ($residentToken) {
            $this->testGet('/api/resident/profile', $residentToken, 'Resident:profile');
        } else {
            $this->skip('GET', '/api/resident/profile', 'Resident:profile', 'No resident user');
        }
        $this->testGet('/api/v1/admin/residents', $adminToken, 'Admin:residents [X-MODULE]');

        // === ROOM ===
        $this->testGet('/api/rooms', $adminToken, 'Room:index');
        if ($roomId) {
            $this->testGet('/api/rooms/'.$roomId, $adminToken, 'Room:show');
        }
        $this->testGet('/api/rooms-schedules', $adminToken, 'Room:schedules [X-MODULE]');

        // === SCHEDULE ===
        $this->testGet('/api/v1/room-schedules', $adminToken, 'Schedule:index');
        if ($scheduleId) {
            $this->testGet('/api/v1/room-schedules/'.$scheduleId, $adminToken, 'Schedule:show');
        }
        if ($residentToken) {
            $this->testGet('/api/v1/room-schedules/my', $residentToken, 'Schedule:my');
        } else {
            $this->skip('GET', '/api/v1/room-schedules/my', 'Schedule:my', 'No resident user');
        }
        if ($roomId) {
            $this->testGet('/api/v1/room-schedules/kamar/'.$roomId, $adminToken, 'Schedule:byRoom');
        }

        // === FINANCE (cross-module) ===
        $this->testGet('/api/finance/dashboard/kpi-summary', $adminToken, 'Finance:kpiSummary [X-MODULE]');
        $this->testGet('/api/finance/dashboard/revenue-chart', $adminToken, 'Finance:revenueChart [X-MODULE]');
        $this->testGet('/api/finance/dashboard/due-invoices', $adminToken, 'Finance:dueInvoices [X-MODULE]');
        $this->testGet('/api/finance/dashboard/pending-payments', $adminToken, 'Finance:pendingPayments [X-MODULE]');
        $this->testGet('/api/finance/expenses', $adminToken, 'Finance:expenses');
        $this->testGet('/api/finance/payments', $adminToken, 'Finance:payments');
        $this->testGet('/api/finance/invoices', $adminToken, 'Finance:invoices [X-MODULE]');
        if ($invoiceId) {
            $this->testGet('/api/finance/invoices/'.$invoiceId, $adminToken, 'Finance:invoices.show [X-MODULE]');
        }
        if ($residentToken) {
            $this->testGet('/api/finance/me/summary', $residentToken, 'Finance:me.summary [X-MODULE]');
            $this->testGet('/api/finance/me/invoices', $residentToken, 'Finance:me.invoices [X-MODULE]');
            $this->testGet('/api/finance/me/payments', $residentToken, 'Finance:me.payments');
            $this->testGet('/api/finance/me/fines', $residentToken, 'Finance:me.fines [X-MODULE]');
            $this->testGet('/api/finance/me/refund-requests', $residentToken, 'Finance:me.refundRequests');
        } else {
            $this->skip('GET', '/api/finance/me/*', 'Finance:me.*', 'No resident user');
        }
        $this->testGet('/api/finance/payment-methods', $adminToken, 'Finance:paymentMethods');
        $this->testGet('/api/finance/refund-requests', $adminToken, 'Finance:refundRequests');
        $this->testGet('/api/finance/fixed-expenses', $adminToken, 'Finance:fixedExpenses');

        // === GUEST (cross-module) ===
        if ($residentToken) {
            $this->testGet('/api/guests', $residentToken, 'Guest:index [X-MODULE]');
        } else {
            $this->skip('GET', '/api/guests', 'Guest:index', 'No resident user');
        }
        $this->testGet('/api/admin/guests', $adminToken, 'Guest:admin.index [X-MODULE]');
        $this->testGet('/api/admin/guest-bills', $adminToken, 'Guest:admin.bills [X-MODULE]');

        // === MAINTENANCE ===
        if ($residentToken) {
            $this->testGet('/api/v1/damage-reports/my-reports', $residentToken, 'Maint:myReports');
        } else {
            $this->skip('GET', '/api/v1/damage-reports/my-reports', 'Maint:myReports', 'No resident user');
        }
        $this->testGet('/api/v1/damage-reports/admin', $adminToken, 'Maint:admin.index');
        $this->testGet('/api/v1/schedules', $adminToken, 'Maint:schedules.index');

        // === INVENTORY ===
        $this->testGet('/api/inventory', $adminToken, 'Inventory:index');
        if ($inventoryId) {
            $this->testGet('/api/inventory/'.$inventoryId, $adminToken, 'Inventory:show');
        }

        // === NOTIFICATION ===
        $this->testGet('/api/notification/logs', $adminToken, 'Notif:logs');
        $this->testGet('/api/notification/logs/summary', $adminToken, 'Notif:summary');
        $this->testGet('/api/notification/recipients', $adminToken, 'Notif:recipients');

        // === SETTING ===
        $this->testGet('/api/v1/settings/public', '', 'Setting:public (no auth)');
        $this->testGet('/api/v1/settings', $adminToken, 'Setting:index');
        $this->testGet('/api/v1/settings/bank-accounts', $adminToken, 'Setting:bankAccounts');
        $this->testGet('/api/v1/settings/bank-accounts/public', '', 'Setting:bankAccounts.public');
        $this->testGet('/api/v1/settings/feature-toggles', $adminToken, 'Setting:featureToggles');
        $this->testGet('/api/v1/settings/midtrans-fees', $adminToken, 'Setting:midtransFees');
        $this->testGet('/api/v1/settings/payment-methods', $adminToken, 'Setting:paymentMethods');

        // === DASHBOARD (cross-module: aggregates everything) ===
        $this->testGet('/api/dashboard/admin', $adminToken, 'Dashboard:adminStats [X-MODULE]');
        if ($residentToken) {
            $this->testGet('/api/dashboard/resident', $residentToken, 'Dashboard:residentStats [X-MODULE]');
        } else {
            $this->skip('GET', '/api/dashboard/resident', 'Dashboard:residentStats', 'No resident user');
        }

        // === OUTPUT ===
        $this->newLine(2);
        $this->info('=== ENDPOINT MATRIX RESULTS ===');
        
        $totalOk = 0;
        $totalFail = 0;
        $totalSkip = 0;
        $crossModule = [];
        
        $headers = ['Label', 'Method', 'URI', 'Status', 'OK?', 'Keys', 'Note'];
        $rows = [];
        
        foreach ($this->results as $r) {
            $ok = '✅';
            if ($r['status'] === 'SKIP') {
                $ok = '⏭️';
                $totalSkip++;
            } elseif ($r['ok']) {
                $ok = '✅';
                $totalOk++;
            } else {
                $ok = '❌';
                $totalFail++;
            }
            
            $rows[] = [$r['label'], $r['method'], $r['uri'], $r['status'], $ok, substr($r['keys'], 0, 35), substr($r['note'], 0, 50)];
            
            if (str_contains($r['label'], '[X-MODULE]')) {
                $crossModule[] = $r;
            }
        }
        
        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info("TOTAL: {$totalOk} OK, {$totalFail} FAILED, {$totalSkip} SKIPPED");
        
        $this->newLine();
        $this->info('=== CROSS-MODULE ENDPOINTS (High Risk) ===');
        foreach ($crossModule as $r) {
            $icon = $r['ok'] ? '✅' : '❌';
            $this->line("  {$icon} [{$r['status']}] {$r['label']} → {$r['uri']}");
        }
        
        // Cleanup
        $admin->tokens()->where('name', 'test-admin')->delete();
        if ($resident) {
            $resident->tokens()->where('name', 'test-resident')->delete();
        }
        
        $this->newLine();
        $this->info('=== DONE ===');
        
        return $totalFail > 0 ? 1 : 0;
    }

    private function testGet(string $uri, string $token, string $label): void
    {
        try {
            $request = Request::create($uri, 'GET');
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
            $request->headers->set('Accept', 'application/json');
            
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle($request);
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getContent(), true);
            
            $keys = is_array($content) ? implode(', ', array_slice(array_keys($content), 0, 6)) : '';
            $ok = $statusCode >= 200 && $statusCode < 500;
            $note = '';
            if ($statusCode === 403) {
                $note = 'Forbidden (permission)';
                $ok = true; // 403 is expected behavior, not a crash
            } elseif ($statusCode >= 500) {
                $note = 'SERVER ERROR: ' . ($content['message'] ?? 'Unknown');
            }
            
            $this->results[] = compact('label', 'uri', 'statusCode', 'ok', 'keys', 'note') + ['method' => 'GET', 'status' => (string)$statusCode];
            
            // Reset app state
            $kernel->terminate($request, $response);
            
        } catch (\Throwable $e) {
            $this->results[] = [
                'method' => 'GET',
                'uri' => $uri,
                'label' => $label,
                'status' => 'EXC',
                'ok' => false,
                'keys' => '',
                'note' => 'EXCEPTION: ' . substr($e->getMessage(), 0, 80),
            ];
        }
    }

    private function skip(string $method, string $uri, string $label, string $reason): void
    {
        $this->results[] = [
            'method' => $method,
            'uri' => $uri,
            'label' => $label,
            'status' => 'SKIP',
            'ok' => true,
            'keys' => '',
            'note' => $reason,
        ];
    }
}
