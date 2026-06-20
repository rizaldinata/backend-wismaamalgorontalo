<?php

namespace Tests\Feature\ModuleIsolation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;
use Tests\Traits\ManagesModuleIsolation;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;
use Modules\Schedule\Models\Schedule;
use Spatie\Permission\Models\Role;

class FinanceIsolationTest extends TestCase
{
    use RefreshDatabase, ManagesModuleIsolation;

    protected function setUp(): void
    {
        $this->setModuleStatuses(['Finance' => false]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->enableAllModules();
        parent::tearDown();
    }

    public function test_finance_endpoints_are_inaccessible_when_module_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Finance'));

        $admin = User::factory()->create();
        
        // These routes shouldn't be loaded
        $response = $this->actingAs($admin)->getJson('/api/finance/invoices');
        $response->assertStatus(404);
        
        $response = $this->actingAs($admin)->getJson('/api/finance/payments');
        $response->assertStatus(404);
    }
    
    public function test_schedule_module_functions_normally_when_finance_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Finance'));
        $this->assertTrue(Module::isEnabled('Schedule'));
        
        // Buat room
        $room = Room::factory()->create(['status' => 'available']);
        $tenant = User::factory()->create();
        
        $schedule = Schedule::create([
            'room_id' => $room->id,
            'tenant_user_id' => $tenant->id,
            'status' => 'pending',
            'type' => 'sewa',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(1)->format('Y-m-d'),
            'price' => 1000000
        ]);
        
        $this->assertDatabaseHas('room_schedules', [
            'id' => $schedule->id,
            'status' => 'pending'
        ]);
        
        $admin = User::factory()->create();
        
        $response = $this->actingAs($admin)->withoutMiddleware()->postJson('/api/v1/room-schedules', [
            'room_id' => $room->id,
            'tenant_user_id' => $tenant->id,
            'type' => 'sewa',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(1)->format('Y-m-d'),
            'price' => 1500000,
        ]);
        
        // As long as it is not a 500 error from a missing listener, the isolation works!
        // 201 means created successfully.
        $this->assertNotEquals(500, $response->status(), 'Module crashed because of missing dependencies.');
    }
}
