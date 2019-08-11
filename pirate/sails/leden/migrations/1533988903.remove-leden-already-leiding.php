<?php
namespace Pirate\Sails\Leden\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class RemoveLedenAlreadyLeiding1533988903 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "DELETE FROM inschrijvingen WHERE tak = ''";
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
