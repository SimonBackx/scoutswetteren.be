<?php

// Aantal minuten tussen per cronjob
$cronjobs = array(
    'files' => array(
        'delete-from-server' => 1,
        'upload-to-object-storage' => 1,
        'download-from-object-storage' => 1,
        'zip-albums' => 1,
    ),
);