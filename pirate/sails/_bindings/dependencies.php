<?php

// Alle sails die dependencies gedefinieerd hebben
clearstatcache();
$sails = include __DIR__.'/sails.php';

$dependencies = [];
foreach ($sails as $sail) {
    if (file_exists(__DIR__."/../$sail/dependencies.php")) {
        $dependencies[] = $sail;
    }
} 