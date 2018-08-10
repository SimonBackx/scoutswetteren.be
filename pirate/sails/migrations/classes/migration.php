<?php
namespace Pirate\Classes\Migrations;
use Pirate\Database\Database;

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
