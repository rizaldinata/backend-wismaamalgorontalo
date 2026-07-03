<?php

namespace Tests\Feature\ModuleIsolation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;
use Tests\Traits\ManagesModuleIsolation;

class MaintenanceIsolationTest extends TestCase
{
    use ManagesModuleIsolation, RefreshDatabase;

    protected function setUp(): void
    {
        $this->setModuleStatuses(['Maintenance' => false]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->enableAllModules();
        parent::tearDown();
    }

    public function test_maintenance_endpoints_are_inaccessible_when_module_is_disabled()
    {
        $this->assertFalse(Module::isEnabled('Maintenance'));

        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->getJson('/api/maintenance');
        $response->assertStatus(404);
    }
}
