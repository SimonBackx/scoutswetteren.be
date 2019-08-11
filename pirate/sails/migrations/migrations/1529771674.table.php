<?php
namespace Pirate\Sails\Migrations\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Table1529771674 extends Migration
{

    public static function upgrade(): bool
    {
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

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
