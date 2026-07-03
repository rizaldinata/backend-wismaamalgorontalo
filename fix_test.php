<?php
$file = 'tests/Feature/EndpointMatrixTest.php';
$content = file_get_contents($file);

$content = preg_replace(
    '/\$response\s*=\s*\\\\Laravel\\\\Sanctum\\\\Sanctum::actingAs\(\$this->(admin|resident),\s*\[\'\*\'\]\)\s*->getJson\((.*?)\);/s',
    "\\Laravel\\Sanctum\\Sanctum::actingAs(\$this->\\1, ['*']);\n        \$response = \$this->getJson(\\2);",
    $content
);

file_put_contents($file, $content);
echo "Refactored EndpointMatrixTest.php\n";
