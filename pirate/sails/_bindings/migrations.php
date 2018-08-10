<?php

// Alle sails die dependencies gedefinieerd hebben
clearstatcache();
$sails = include __DIR__.'/sails.php';

$migrations = [];
foreach ($sails as $sail) {
    if (file_exists(__DIR__."/../$sail/migrations")) {
        $files = scandir(__DIR__."/../$sail/migrations");
        foreach ($files as $file) {
            $parts = explode('.', $file);
            if (count($parts) == 3 && $parts[2] == 'php') {
                $timestamp = intval($parts[0]);
                $class = dashesToCamelCase($parts[1], true).$timestamp;

                $migrations[] = (object) [
                    'id' => "$sail/$class",
                    'sail' => $sail,
                    'timestamp' => $timestamp,
                    'class' => $class,
                    'path' => realpath(__DIR__."/../$sail/migrations/$file"),
                ];
            }
        }
    }
} 

return $migrations;