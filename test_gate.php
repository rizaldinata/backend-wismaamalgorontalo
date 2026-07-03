<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

// Migrate and setup
Artisan::call('migrate:fresh');

// Ensure roles exist
$superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
$admin = User::factory()->create(['name' => 'Test Admin']);
$admin->assignRole($superAdminRole);

var_dump($admin->hasRole('super-admin'));
var_dump(Gate::forUser($admin)->check('view-damage-report'));
