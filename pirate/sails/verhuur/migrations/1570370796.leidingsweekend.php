<?php
namespace Pirate\Sails\Verhuur\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Leidingsweekend1570370796 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "ALTER TABLE `verhuur`
        ADD COLUMN `leidingsweekend` int(1) NOT NULL DEFAULT '0' AFTER `aanvraag_datum`;";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
