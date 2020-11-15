<?php
namespace Pirate\Sails\Maandplanning\Migrations;
use Pirate\Sails\Migrations\Classes\Migration;

class Button1605457282 extends Migration {

    static function upgrade(): bool {
        $create_query = "ALTER TABLE `events` ADD COLUMN `button_url` varchar(150) NULL COMMENT '';";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        $create_query = "ALTER TABLE `events` ADD COLUMN `button_title` varchar(50) NULL COMMENT '';";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}