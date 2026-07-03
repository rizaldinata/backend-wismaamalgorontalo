<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Http\Request;

Artisan::call('migrate:fresh');
$superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
$admin = User::factory()->create(['name' => 'Test Admin']);
$admin->assignRole($superAdminRole);
Sanctum::actingAs($admin, ['*']);

$middleware = new PermissionMiddleware();
$request = Request::create('/api/v1/damage-reports/admin', 'GET');
try {
    $middleware->handle($request, function() { echo "Middleware Passed\n"; }, 'view-damage-report');
} catch (Exception $e) {
    echo "Exception: " . get_class($e) . " - " . $e->getMessage() . "\n";
}
