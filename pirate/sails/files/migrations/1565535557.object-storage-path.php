<?php
namespace Pirate\Sails\Files\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class ObjectStoragePath1565535557 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE `files` ADD COLUMN `file_object_storage_path` varchar(350) DEFAULT NULL AFTER `file_object_storage_host`;";

        if ($result = self::getDb()->query($query)) {
            $query = "UPDATE `files` SET `file_object_storage_path` = CONCAT(`file_location`, `file_name`) where `file_object_storage_host` is not null;";
            if ($result = self::getDb()->query($query)) {
                return true;
            }
        }

        throw new \Exception(self::getDb()->error);
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
