<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$generator = new \App\Services\PostmanCollectionGenerator();
$generator->generate();
$generator->saveToFile();

echo "Postman collection generated successfully!\n";
