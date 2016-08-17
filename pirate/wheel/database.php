<?php
namespace Pirate\Database;
use mysqli;

class Database {
    private static $mysqli;

    static public function init() {
        try {
            self::$mysqli = new mysqli('127.0.0.1', 'root', 'root', 'scouts');
            if (self::$mysqli->connect_errno){
                header('Location: /oops/database.html');
                
            }
        }
        catch (Exception $e) {
            header('Location: /oops/database.html');
        }
    }

    static public function getDb() {
        return Self::$mysqli;
    }
}