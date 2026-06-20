<?php

namespace Tests\Feature\ModuleIsolation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;
use Tests\Traits\ManagesModuleIsolation;
use Modules\Auth\Models\User;

class RoomIsolationTest extends TestCase
{
    use RefreshDatabase, ManagesModuleIsolation;

    protected function setUp(): void
    {
        $this->setModuleStatuses(['Room' => false]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->enableAllModules();
        parent::tearDown();
    }

    public function test_room_endpoints_are_inaccessible_when_module_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Room'));

        $admin = User::factory()->create();
        
        $response = $this->actingAs($admin)->getJson('/api/rooms');
        $response->assertStatus(404);
    }
}
