<?php

require 'vendor/autoload.php';
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

try {
    $manager = new ImageManager(new Driver);
    $image = $manager->read('dummy image/Screenshot 2026-06-11 at 9.09.41 PM.png');
    $encoded = $image->toWebp(75);
    echo "WebP Success\n";
} catch (\Throwable $e) {
    echo 'Error: '.get_class($e).' - '.$e->getMessage()."\n";
}
