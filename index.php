<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;

try {
    $app = new Application();
    $app->run();
} catch (Throwable $e) {
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
}