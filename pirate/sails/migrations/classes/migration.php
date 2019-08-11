<?php
namespace Pirate\Sails\Migrations\Classes;
use Pirate\Wheel\Database;

class Migration {

    static function upgrade(): bool {
        throw new \Exception("Migration upgrade is not implemented");
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

    protected static function getDb() {
        return Database::getDb();
    }
}
