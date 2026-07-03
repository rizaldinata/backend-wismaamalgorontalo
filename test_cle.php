<?php
ob_start(function($buffer) {
    if (strpos($buffer, 'cle') !== false) {
        throw new \Exception("OUTPUT CLE DETECTED!");
    }
    return $buffer;
}, 1);

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
try {
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
} catch (\Throwable $e) {
    file_put_contents('cle_trace.log', $e->getMessage() . "\n" . $e->getTraceAsString());
}
