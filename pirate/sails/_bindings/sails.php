<?php

// Een lijst met alle beschikbare sails

$sails = [];

foreach (new DirectoryIterator(__DIR__.'/../') as $fileInfo) {
    if ($fileInfo->isDot()) continue;
    if ($fileInfo->getFilename() == "_bindings") continue;

    $sails[] = $fileInfo->getFilename();
}

return $sails;