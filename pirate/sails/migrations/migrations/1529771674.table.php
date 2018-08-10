<?php
namespace Pirate\Classes\Migrations;
use Pirate\Classes\Migrations\Migration;

class Table1529771674 extends Migration {

    static function upgrade(): bool {
        $query = "CREATE TABLE `migrations` (
            `migration_id` varchar(60) NOT NULL DEFAULT '',
            `migration_executed_at` datetime NOT NULL,
            PRIMARY KEY (`migration_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if ($result = self::getDb()->query($query)) {
            return true;
        }

        return false;

    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}