<?php

// Alle modules die routes bevatten, in de gebruikte volgorde
clearstatcache();
$sails = include __DIR__.'/sails.php';

$routes = [];
foreach ($sails as $sail) {
    if (file_exists(__DIR__."/../$sail/routes.php")) {
        $routes[] = $sail;
    }
}

//$routes = array('homepage', 'info', 'photos', 'blog', 'leiding', 'leden', 'verhuur', 'contact');