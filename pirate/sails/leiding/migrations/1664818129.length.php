<?php
namespace Pirate\Sails\Leiding\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class Length1664818129 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE `leiding` CHANGE `totem` `totem` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '', CHANGE `roepnaam` `roepnaam` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;";

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
