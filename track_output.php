<?php
declare(ticks=1);
$lastLength = 0;
register_tick_function(function() use (&$lastLength) {
    $currentLength = ob_get_length();
    if ($currentLength > $lastLength) {
        $lastLength = $currentLength;
        echo "\n\nOUTPUT DETECTED!\n";
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        foreach ($bt as $b) {
            echo "File: " . ($b['file'] ?? 'unknown') . " Line: " . ($b['line'] ?? 'unknown') . "\n";
        }
        exit;
    }
});

ob_start();
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
