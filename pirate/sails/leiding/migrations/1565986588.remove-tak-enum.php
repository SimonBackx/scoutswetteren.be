<?php
namespace Pirate\Sails\Leiding\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class RemoveTakEnum1565986588 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE leiding MODIFY `tak` varchar(40) DEFAULT ''";

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
