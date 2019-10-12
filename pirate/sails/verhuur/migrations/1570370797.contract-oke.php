<?php
namespace Pirate\Sails\Verhuur\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class ContractOke1570370797 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "ALTER TABLE `verhuur`
        ADD COLUMN `contract_oke` int(1) NOT NULL DEFAULT '0' AFTER `aanvraag_datum`;";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        $update_query = "UPDATE `verhuur` set `contract_oke` = `ligt_vast`";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($update_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
