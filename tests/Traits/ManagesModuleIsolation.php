<?php

namespace Tests\Traits;

trait ManagesModuleIsolation
{
    /**
     * Set the statuses for specific modules before the application boots.
     * This method must be called before parent::setUp() in your test's setUp method.
     * 
     * @param array $statuses e.g. ['Finance' => false]
     */
    protected function setModuleStatuses(array $statuses): void
    {
        $defaultStatuses = [
            'Room' => true,
            'Finance' => true,
            'Maintenance' => true,
            'Auth' => true,
            'Inventory' => true,
            'Setting' => true,
            'Notification' => true,
            'Guest' => true,
            'Schedule' => true,
        ];

        $merged = array_merge($defaultStatuses, $statuses);
        
        $testingFile = dirname(__DIR__, 2) . '/modules_statuses_testing.json';
        
        file_put_contents($testingFile, json_encode($merged, JSON_PRETTY_PRINT));
    }
    
    /**
     * Restore all modules to enabled.
     */
    protected function enableAllModules(): void
    {
        $this->setModuleStatuses([]);
        
        // Force Laravel to re-migrate the database for the next test
        // because the disabled module might have skipped migrations.
        if (class_exists(\Illuminate\Foundation\Testing\RefreshDatabaseState::class)) {
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        }
    }
}
