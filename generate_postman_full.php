<?php

use App\Services\PostmanCollectionGenerator;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$generator = new PostmanCollectionGenerator();
$generator->generate();
$path = $generator->saveToFile();

echo "Postman collection generated successfully at: " . $path . PHP_EOL;
