<?php
sleep(1);
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__.'/../wheel/ship.php');


$ship = new Pirate\Ship();
if (!$ship->install()) {
    exit(1);
}