#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    require __DIR__.'/bootstrap/autoload.php';
}

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
exit($kernel->handle(new Symfony\Component\Console\Input\ArgvInput));