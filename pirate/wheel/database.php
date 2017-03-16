<?php
namespace Pirate\Database;
use mysqli;

class Database {
    private static $mysqli;

    static public function init() {
        try {
            if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
                self::$mysqli = new mysqli('db', 'root', 'root', 'scouts');
            } else {
                self::$mysqli = new mysqli('127.0.0.1', 'root', 'root', 'scouts');
            }

            if (self::$mysqli->connect_errno){
                header('Location: /oops/database.html');
                die();
            }
            self::$mysqli->set_charset("utf8mb4");
        }
        catch (Exception $e) {
            header('Location: /oops/database.html');
            die();
        }
    }

    static public function getDb() {
        return Self::$mysqli;
    }
}