<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;

class RemoveLedenAlreadyLeiding1533988903 extends Migration {

    static function upgrade(): bool {
        $query = "DELETE FROM inschrijvingen WHERE tak = ''";
        if (!self::getDb()->query($query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}