<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\InvoiceType;
use Modules\Inventory\Models\Inventory;
use Modules\Inventory\Enums\ItemCondition;
use Modules\Room\Models\Room;
use Modules\Room\Enums\RoomStatus;
use Modules\Schedule\Models\Schedule;
use Modules\Schedule\Enums\ScheduleStatus;
use Modules\Schedule\Enums\ScheduleType;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EndpointMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $resident;
    private Room $room;
    private ?Schedule $schedule = null;
    private ?Invoice $invoice = null;
    private ?Inventory $inventory = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock FeatureToggleService so tests don't fail due to disabled modules
        $this->mock(\Modules\Setting\Services\FeatureToggleService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('getHierarchicalToggles')->andReturn([]);
        });

        // Ensure roles exist
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $residentRole = Role::firstOrCreate(['name' => 'resident', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);

        // Give resident role required permissions for tests
        $residentPermissions = [
            'finance-me-summary-view',
            'finance-me-invoice-view',
            'finance-me-payment-view',
            'finance-me-fine-view',
            'view-my-damage-report',
            'view-resident-dashboard',
            'view-my-guest'
        ];
        
        foreach ($residentPermissions as $perm) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
            $residentRole->givePermissionTo($permission);
        }

        // Create admin with super-admin role
        $this->admin = User::factory()->create(['name' => 'Test Admin']);
        $this->admin->assignRole($superAdminRole);

        // Create resident
        $this->resident = User::factory()->create(['name' => 'Test Resident', 'email' => 'resident@test.com']);
        $this->resident->assignRole($residentRole);

        \Modules\Auth\Models\UserProfile::create([
            'user_id' => $this->resident->id,
            'id_card_number' => '1234567890123456',
            'phone_number' => '08123456789',
            'gender' => 'male',
            'address_ktp' => 'Jl. Test No. 123',
        ]);

        // Create room
        $this->room = Room::create([
            'number' => 'T-101',
            'floor' => 1,
            'type' => 'single',
            'status' => RoomStatus::OCCUPIED,
            'price' => 500000,
            'description' => 'Test room',
        ]);

        // Create schedule
        $this->schedule = Schedule::create([
            'room_id' => $this->room->id,
            'type' => ScheduleType::SEWA,
            'status' => ScheduleStatus::ACTIVE,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'tenant_user_id' => $this->resident->id,
            'tenant_name' => 'Test Resident',
            'tenant_phone' => '08123456789',
            'agreed_price' => 500000,
            'payment_scheme' => 'full',
        ]);

        // Create invoice
        $this->invoice = Invoice::create([
            'schedule_id' => $this->schedule->id,
            'type' => InvoiceType::SEWA,
            'invoice_number' => 'INV-TEST-001',
            'amount' => 500000,
            'status' => InvoiceStatus::UNPAID,
            'due_date' => now()->addDays(7),
            'tenant_user_id' => $this->resident->id,
            'tenant_name' => 'Test Resident',
            'tenant_phone' => '08123456789',
            'room_number' => 'T-101',
            'period_start' => now()->subMonth(),
            'period_end' => now(),
        ]);

        // Create inventory
        $this->inventory = Inventory::create([
            'name' => 'Test Item',
            'quantity' => 10,
            'condition' => ItemCondition::GOOD,
            'location' => 'Gudang',
        ]);
    }

    // ========================================
    // AUTH ENDPOINTS
    // ========================================
    public function test_auth_me_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/me');
        $response->assertStatus(200);
    }

    public function test_auth_permissions_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/permissions');
        $response->assertStatus(200);
    }

    // ========================================
    // ADMIN ENDPOINTS
    // ========================================
    public function test_admin_users_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/admin/users');
        $response->assertStatus(200);
    }

    public function test_admin_users_show_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/admin/users/' . $this->admin->id);
        $response->assertStatus(200);
    }

    public function test_admin_roles_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/admin/roles');
        $response->assertStatus(200);
    }

    // ========================================
    // RESIDENT ENDPOINTS
    // ========================================
    public function test_resident_profile_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/resident/profile');
        $response->assertSuccessful();
    }

    public function test_admin_residents_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/admin/residents');
        $response->assertStatus(200);
    }

    // ========================================
    // ROOM ENDPOINTS
    // ========================================
    public function test_rooms_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/rooms');
        $response->assertStatus(200);
    }

    public function test_rooms_show_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/rooms/' . $this->room->id);
        $response->assertStatus(200);
    }

    public function test_rooms_schedules_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/rooms-schedules');
        $response->assertStatus(200);
    }

    // ========================================
    // SCHEDULE ENDPOINTS
    // ========================================
    public function test_schedule_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/room-schedules');
        $response->assertStatus(200);
    }

    public function test_schedule_show_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/room-schedules/' . $this->schedule->id);
        $response->assertStatus(200);
    }

    public function test_schedule_my_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/v1/room-schedules/my');
        $response->assertStatus(200);
    }

    public function test_schedule_by_room_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/room-schedules/kamar/' . $this->room->id);
        $response->assertStatus(200);
    }

    // ========================================
    // FINANCE ENDPOINTS (Cross-Module: Finance <-> Schedule)
    // ========================================
    public function test_finance_kpi_summary_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/dashboard/kpi-summary');
        $response->assertStatus(200);
    }

    public function test_finance_revenue_chart_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/dashboard/revenue-chart');
        $response->assertStatus(200);
    }

    public function test_finance_due_invoices_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/dashboard/due-invoices');
        $response->assertStatus(200);
    }

    public function test_finance_pending_payments_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/dashboard/pending-payments');
        $response->assertStatus(200);
    }

    public function test_finance_expenses_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/expenses');
        $response->assertStatus(200);
    }

    public function test_finance_payments_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/payments');
        $response->assertStatus(200);
    }

    public function test_finance_invoices_index_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/invoices');
        $response->assertStatus(200);
    }

    public function test_finance_invoices_show_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/invoices/' . $this->invoice->id);
        $response->assertStatus(200);
    }

    public function test_finance_me_summary_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/finance/me/summary');
        $response->assertStatus(200);
    }

    public function test_finance_me_invoices_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/finance/me/invoices');
        $response->assertStatus(200);
    }

    public function test_finance_me_payments_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/finance/me/payments');
        $response->assertStatus(200);
    }

    public function test_finance_me_fines_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/finance/me/fines');
        $response->assertStatus(200);
    }

    public function test_finance_payment_methods_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/payment-methods');
        $response->assertStatus(200);
    }

    public function test_finance_refund_requests_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/refund-requests');
        $response->assertStatus(200);
    }

    public function test_finance_fixed_expenses_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/finance/fixed-expenses');
        $response->assertStatus(200);
    }

    // ========================================
    // GUEST ENDPOINTS (Cross-Module: Guest <-> Schedule)
    // ========================================
    public function test_guest_index_returns_200_cross_module(): void
    {
        // Guest needs GuestActiveContext
        \Modules\Guest\Models\GuestActiveContext::create([
            'user_id' => $this->resident->id,
            'schedule_id' => $this->schedule->id,
            'room_id' => $this->room->id,
            'tenant_name' => $this->resident->name,
            'tenant_phone' => '08123456789',
            'is_active' => true,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/guests');
        $response->assertStatus(200);
    }

    public function test_guest_admin_index_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/admin/guests');
        $response->assertStatus(200);
    }

    public function test_guest_admin_bills_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/admin/guest-bills');
        $response->assertStatus(200);
    }

    // ========================================
    // MAINTENANCE ENDPOINTS
    // ========================================
    public function test_maintenance_my_reports_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/v1/damage-reports/my-reports');
        $response->assertStatus(200);
    }

    public function test_maintenance_admin_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/damage-reports/admin');
        $response->assertStatus(200);
    }

    public function test_maintenance_schedules_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/schedules');
        $response->assertStatus(200);
    }

    // ========================================
    // INVENTORY ENDPOINTS
    // ========================================
    public function test_inventory_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/inventory');
        $response->assertStatus(200);
    }

    public function test_inventory_show_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/inventory/' . $this->inventory->id);
        $response->assertStatus(200);
    }

    // ========================================
    // NOTIFICATION ENDPOINTS
    // ========================================
    public function test_notification_logs_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/notification/logs');
        $response->assertStatus(200);
    }

    public function test_notification_summary_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/notification/logs/summary');
        $response->assertStatus(200);
    }

    public function test_notification_recipients_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/notification/recipients');
        $response->assertStatus(200);
    }

    // ========================================
    // SETTING ENDPOINTS
    // ========================================
    public function test_settings_public_returns_200_no_auth(): void
    {
        $response = $this->getJson('/api/v1/settings/public');
        $response->assertStatus(200);
    }

    public function test_settings_index_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/settings');
        $response->assertStatus(200);
    }

    public function test_settings_bank_accounts_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/settings/bank-accounts');
        $response->assertStatus(200);
    }

    public function test_settings_bank_accounts_public_returns_200(): void
    {
        $response = $this->getJson('/api/v1/settings/bank-accounts/public');
        $response->assertStatus(200);
    }

    public function test_settings_feature_toggles_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/settings/feature-toggles');
        $response->assertStatus(200);
    }

    public function test_settings_midtrans_fees_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/settings/midtrans-fees');
        $response->assertStatus(200);
    }

    public function test_settings_payment_methods_returns_200(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/v1/settings/payment-methods');
        $response->assertStatus(200);
    }

    // ========================================
    // DASHBOARD ENDPOINTS (Cross-Module: aggregates ALL)
    // ========================================
    public function test_dashboard_admin_stats_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->admin, ['*']);
        $response = $this->getJson('/api/dashboard/admin');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_rooms',
                    'occupied_rooms',
                    'empty_rooms',
                    'total_residents',
                    'monthly_income',
                    'recent_activities',
                ],
            ]);
    }

    public function test_dashboard_resident_stats_returns_200_cross_module(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->resident, ['*']);
        $response = $this->getJson('/api/dashboard/resident');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'active_room',
                    'recent_bills',
                ],
            ]);
    }
}
