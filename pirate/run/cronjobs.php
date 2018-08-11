<?php

$_SERVER['HTTPS'] = true;
$_SERVER['SERVER_NAME'] = 'www.scoutswetteren.be';
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__.'/../wheel/ship.php');

$ship = new Pirate\Ship();
$ship->cronjobs();

?>