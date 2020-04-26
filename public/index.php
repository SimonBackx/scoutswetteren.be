<?php
if (isset($_SERVER['DEBUG'])) {
    // Env variable moved to server randomly without a reason... Can't find the issue.
    $_ENV['DEBUG'] = $_SERVER['DEBUG'];
}

require __DIR__ . '/../pirate/run/http.php';
