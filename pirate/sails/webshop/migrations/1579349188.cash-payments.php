<?php
namespace Pirate\Sails\Webshop\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class CashPayments1579349188 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "ALTER TABLE `bank_accounts` ADD COLUMN `account_allow_cash` tinyint(1) NOT NULL DEFAULT '0'";

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

/*
`account_allow_cash` tinyint(1) NOT NULL DEFAULT '0',
 */
