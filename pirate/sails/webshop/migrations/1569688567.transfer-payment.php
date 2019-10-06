<?php
namespace Pirate\Sails\Webshop\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class TransferPayment1569688567 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "CREATE TABLE `payment_transfer` (
            `transfer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `transfer_order` int(11) unsigned NOT NULL,
            `transfer_bank_account` int(11) unsigned NOT NULL,
            `transfer_reference` varchar(80) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
            `transfer_status` varchar(40) CHARACTER SET utf8mb4 NOT NULL DEFAULT 'pending',
            PRIMARY KEY (`transfer_id`),
            KEY `transfer_order` (`transfer_order`),
            KEY `transfer_bank_account` (`transfer_bank_account`),
            CONSTRAINT `payment_transfer_ibfk_1` FOREIGN KEY (`transfer_order`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `payment_transfer_ibfk_2` FOREIGN KEY (`transfer_bank_account`) REFERENCES `bank_accounts` (`account_id`) ON UPDATE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

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

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
