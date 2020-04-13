<?php
namespace Pirate\Sails\Webshop\Migrations;
use Pirate\Sails\Migrations\Classes\Migration;

class Delivery1586782655 extends Migration {

    static function upgrade(): bool {
        $query = "ALTER TABLE `order_sheets` ADD COLUMN `sheet_delivery` tinyint(1) NOT NULL DEFAULT '0'";

        if (!self::getDb()->multi_query($query)) {
            throw new \Exception(self::getDb()->error);
        }

        do {
            if ($res = self::getDb()->store_result()) {
                $res->free();
            }
        } while (self::getDb()->more_results() && self::getDb()->next_result());
        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}