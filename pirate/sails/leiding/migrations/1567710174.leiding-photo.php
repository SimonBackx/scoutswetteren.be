<?php
namespace Pirate\Sails\Leiding\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class LeidingPhoto1567710174 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE leiding ADD `photo_id` int(11) unsigned DEFAULT NULL;";

        if (!self::getDb()->query($query)) {
            throw new \Exception(self::getDb()->error);
        }

        $query = "ALTER TABLE leiding ADD CONSTRAINT `fk_leiding_images` FOREIGN KEY (`photo_id`) REFERENCES `images` (`image_id`) ON DELETE SET NULL ON UPDATE CASCADE";

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
