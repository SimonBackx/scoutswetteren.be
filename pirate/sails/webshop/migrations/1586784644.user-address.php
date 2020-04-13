<?php
namespace Pirate\Sails\Webshop\Migrations;
use Pirate\Sails\Migrations\Classes\Migration;

class UserAddress1586784644 extends Migration {

    static function upgrade(): bool {
        $query = "ALTER TABLE `order_users` ADD COLUMN `order_user_address` varchar(250) NULL, ADD COLUMN `order_user_zipcode` varchar(4) NULL, ADD COLUMN `order_user_city` varchar(250) NULL";

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