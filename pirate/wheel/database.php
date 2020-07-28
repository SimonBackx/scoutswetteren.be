<?php
namespace Pirate\Wheel;

use mysqli;
use Pirate\Sails\Environment\Classes\Environment;

class Database
{
    private static $mysqli;

    public static function init()
    {
        try {
            if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
                self::$mysqli = new mysqli('host.docker.internal', Environment::getSetting('mysql.username'), '', Environment::getSetting('mysql.database'));
            } else {
                self::$mysqli = new mysqli('127.0.0.1', Environment::getSetting('mysql.username'), Environment::getSetting('mysql.password'), Environment::getSetting('mysql.database'));
            }

            if (self::$mysqli->connect_errno) {
                header('Location: /oops/database.html');
                die();
            }
            self::$mysqli->set_charset("utf8mb4");
        } catch (\Exception $e) {
            header('Location: /oops/database.html');
            die();
        }
    }

    public static function getDb()
    {
        return Self::$mysqli;
    }
}
