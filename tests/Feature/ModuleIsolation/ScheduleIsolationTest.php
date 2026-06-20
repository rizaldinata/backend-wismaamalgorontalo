<?php

namespace Tests\Feature\ModuleIsolation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;
use Tests\Traits\ManagesModuleIsolation;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;

class ScheduleIsolationTest extends TestCase
{
    use RefreshDatabase, ManagesModuleIsolation;

    protected function setUp(): void
    {
        $this->setModuleStatuses(['Schedule' => false]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->enableAllModules();
        parent::tearDown();
    }

    public function test_schedule_endpoints_are_inaccessible_when_module_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Schedule'));

        $admin = User::factory()->create();
        
        // This should return 404 because the module is disabled
        $response = $this->actingAs($admin)->getJson('/api/v1/room-schedules');
        $response->assertStatus(404);
    }
    
    public function test_room_module_functions_normally_when_schedule_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Schedule'));
        $this->assertTrue(Module::isEnabled('Room'));
        
        $admin = User::factory()->create();
        
        $response = $this->actingAs($admin)->withoutMiddleware()->postJson('/api/rooms', [
            'room_number' => '101A',
            'type' => 'standard',
            'price_per_month' => 1000000,
            'status' => 'available'
        ]);
        
        $this->assertNotEquals(500, $response->status(), 'Module crashed because of missing dependencies.');
    }
}
