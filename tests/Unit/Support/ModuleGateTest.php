<?php

namespace Tests\Unit\Support;

use App\Support\ModuleGate;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModuleGateTest extends TestCase
{
    public function test_module_is_active_returns_true_when_module_exists_and_enabled()
    {
        Module::shouldReceive('has')->with('TestModule')->andReturn(true);
        Module::shouldReceive('isEnabled')->with('TestModule')->andReturn(true);

        $this->assertTrue(ModuleGate::isActive('TestModule'));
    }

    public function test_module_is_active_returns_false_when_module_does_not_exist()
    {
        Module::shouldReceive('has')->with('TestModule')->andReturn(false);
        // isEnabled should not be called since has() is false, but just in case
        Module::shouldReceive('isEnabled')->with('TestModule')->andReturn(false)->byDefault();

        $this->assertFalse(ModuleGate::isActive('TestModule'));
    }

    public function test_module_is_active_returns_false_when_module_exists_but_disabled()
    {
        Module::shouldReceive('has')->with('TestModule')->andReturn(true);
        Module::shouldReceive('isEnabled')->with('TestModule')->andReturn(false);

        $this->assertFalse(ModuleGate::isActive('TestModule'));
    }
}
