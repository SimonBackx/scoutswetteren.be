<?php
if (isset($_SERVER['DEBUG']) && !isset($_ENV['DEBUG'])) {
    $_ENV['DEBUG'] = $_SERVER['DEBUG'];
}

$config = include __DIR__ . '/../config.php';
$_SERVER['HTTPS'] = true;
$_SERVER['SERVER_NAME'] = ($config['force_www'] ? 'www.' : '') . $config['domain'];

sleep(1);
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../wheel/ship.php';

$ship = new Pirate\Wheel\Ship();
if (!$ship->install()) {
    exit(1);
}
