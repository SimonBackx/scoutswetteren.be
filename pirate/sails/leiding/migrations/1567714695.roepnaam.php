<?php
namespace Pirate\Sails\Leiding\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Roepnaam1567714695 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE leiding ADD `roepnaam` varchar(50) DEFAULT NULL;";

        if (!self::getDb()->query($query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
