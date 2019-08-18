<?php

use Pirate\Sails\Environment\Classes\Environment;
// Een lijst met alle beschikbare sails

$sails = [];

foreach (new DirectoryIterator(__DIR__ . '/../') as $fileInfo) {
    if ($fileInfo->isDot()) {
        continue;
    }

    if ($fileInfo->getFilename() == "_bindings") {
        continue;
    }

    if (!class_exists('Pirate\Sails\Environment\Classes\Environment')) {
        require __DIR__ . '/../environment/classes/environment.php';
    }

    if (in_array($fileInfo->getFilename(), Environment::getSetting('disable_sails', []))) {
        continue;
    }

    $sails[] = $fileInfo->getFilename();
}

return $sails;
