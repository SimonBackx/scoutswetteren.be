<?php

// Lijst met modules die de admin.php hebben met een AdminRouter
clearstatcache();
$sails = include __DIR__ . '/sails.php';

$admin_routes = [];
$admin_pages = [];

foreach ($sails as $sail) {
    if (file_exists(__DIR__ . "/../$sail/admin.php")) {
        $admin_routes[] = $sail;
        $ucfirst_module = ucfirst($sail);

        require_once __DIR__ . "/../$sail/admin.php";
        $classname = "\\Pirate\\Sails\\$ucfirst_module\\{$ucfirst_module}AdminRouter";

        $available_pages = $classname::getAvailablePages();
        foreach ($available_pages as $permission => $pages) {
            foreach ($pages as $page) {
                $admin_pages[$permission][] = $page;
            }
        }
    }
}
