<?php
namespace Pirate\Sails\Blog\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Editor1575718773 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "ALTER TABLE articles
        ADD COLUMN `json` JSON DEFAULT NULL AFTER `text`;";

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
