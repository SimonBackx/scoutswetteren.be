<?php

// Alle geregistreerde sails die Api routes ontvangen op:
// /api/sailnaam/...
// d.m.v. routes
//$api_routes = array('maandplanning', 'blog', 'verhuur', 'photos', 'leden');

clearstatcache();
$sails = include __DIR__ . '/sails.php';

$api_routes = [];
foreach ($sails as $sail) {
    if (file_exists(__DIR__ . "/../$sail/api.php")) {
        $api_routes[] = $sail;
    }
}
