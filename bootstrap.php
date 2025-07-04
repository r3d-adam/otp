<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Utils.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
