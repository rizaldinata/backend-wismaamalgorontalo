<?php

namespace Tests\Feature\ModuleIsolation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;
use Tests\Traits\ManagesModuleIsolation;
use Modules\Auth\Models\User;
use Modules\Room\Models\Room;
use Modules\Schedule\Models\Schedule;

class NotificationIsolationTest extends TestCase
{
    use RefreshDatabase, ManagesModuleIsolation;

    protected function setUp(): void
    {
        $this->setModuleStatuses(['Notification' => false]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->enableAllModules();
        parent::tearDown();
    }

    public function test_notification_endpoints_are_inaccessible_when_module_is_disabled(): void
    {
        $this->assertFalse(Module::isEnabled('Notification'));

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->getJson('/api/notification/logs');
        $response->assertStatus(404);

        $response = $this->actingAs($admin)->postJson('/api/notification/send');
        $response->assertStatus(404);
    }

    public function test_schedule_module_functions_normally_when_notification_is_disabled(): void
    {
        $this->assertFalse(Module::isEnabled('Notification'));
        $this->assertTrue(Module::isEnabled('Schedule'));

        $room   = Room::factory()->create(['status' => 'available']);
        $tenant = User::factory()->create();
        $admin  = User::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()->postJson('/api/v1/room-schedules', [
            'room_id'        => $room->id,
            'tenant_user_id' => $tenant->id,
            'type'           => 'sewa',
            'start_date'     => now()->format('Y-m-d'),
            'end_date'       => now()->addMonths(1)->format('Y-m-d'),
            'price'          => 1500000,
        ]);

        $this->assertNotEquals(500, $response->status(), 'Modul Schedule crash karena Notification tidak aktif.');
    }

    public function test_finance_module_functions_normally_when_notification_is_disabled(): void
    {
        $this->assertFalse(Module::isEnabled('Notification'));
        $this->assertTrue(Module::isEnabled('Finance'));

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->withoutMiddleware()->getJson('/api/finance/invoices');

        $this->assertNotEquals(500, $response->status(), 'Modul Finance crash karena Notification tidak aktif.');
    }
}
