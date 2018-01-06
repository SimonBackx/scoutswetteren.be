<?php

require(__DIR__ . '/../pirate/vendor/autoload.php');
require(__DIR__.'/../pirate/wheel/ship.php');

$ship = new Pirate\Ship();
$ship->sail();