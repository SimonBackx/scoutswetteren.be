<?php

$config = include __DIR__ . '/../config.php';
$_SERVER['HTTPS'] = true;
$_SERVER['SERVER_NAME'] = ($config['force_www'] ? 'www.' : '') . $config['domain'];

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../wheel/ship.php';

$ship = new Pirate\Ship();
$ship->cronjobs();
