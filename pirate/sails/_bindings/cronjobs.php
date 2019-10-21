<?php

// Aantal minuten tussen per cronjob
$cronjobs = array(
    'groepsadmin' => array(
        'sync' => 60 * 24,
    ),
    'files' => array(
        'delete-from-server' => 1,
        'upload-to-object-storage' => 1,
        'download-from-object-storage' => 1,
        'zip-albums' => 1,
    ),
    'amazonSes' => array(
        'send' => 1,
    ),
);
