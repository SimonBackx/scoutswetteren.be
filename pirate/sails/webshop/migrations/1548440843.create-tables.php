<?php
namespace Pirate\Classes\Webshop;

use Pirate\Classes\Migrations\Migration;

class CreateTables1548440843 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "SET NAMES utf8mb4;
        SET FOREIGN_KEY_CHECKS=0;

        # Dump of table _order_item_options
        # ------------------------------------------------------------

        CREATE TABLE `_order_item_options` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_item_id` int(11) unsigned NOT NULL,
          `product_option_id` int(11) unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;



        # Dump of table _order_sheet_products
        # ------------------------------------------------------------

        CREATE TABLE `_order_sheet_products` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_sheet_id` int(11) unsigned NOT NULL,
          `product_id` int(11) unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `order_sheet_id` (`order_sheet_id`,`product_id`),
          KEY `product_id` (`product_id`),
          CONSTRAINT `_order_sheet_products_ibfk_1` FOREIGN KEY (`order_sheet_id`) REFERENCES `order_sheets` (`sheet_id`) ON DELETE CASCADE ON UPDATE CASCADE,
          CONSTRAINT `_order_sheet_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table bank_accounts
        # ------------------------------------------------------------

        CREATE TABLE `bank_accounts` (
          `account_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `account_name` varchar(50) NOT NULL DEFAULT '',
          `account_iban` varchar(50) DEFAULT NULL,
          `account_stripe_public` varchar(100) DEFAULT NULL,
          `account_stripe_secret` varchar(100) DEFAULT NULL,
          PRIMARY KEY (`account_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table order_items
        # ------------------------------------------------------------

        CREATE TABLE `order_items` (
          `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `item_order` int(11) unsigned NOT NULL,
          `item_amount` int(11) NOT NULL,
          `item_person_name` varchar(50) DEFAULT NULL,
          `item_product` int(11) unsigned NOT NULL,
          `item_product_price` int(11) unsigned NOT NULL,
          `item_total` int(11) NOT NULL,
          PRIMARY KEY (`item_id`),
          KEY `item_product` (`item_product`),
          KEY `item_product_price` (`item_product_price`),
          KEY `item_order` (`item_order`),
          CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`item_product`) REFERENCES `products` (`product_id`) ON UPDATE CASCADE,
          CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_product_price`) REFERENCES `product_prices` (`price_id`) ON UPDATE CASCADE,
          CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`item_order`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;



        # Dump of table order_sheets
        # ------------------------------------------------------------

        CREATE TABLE `order_sheets` (
            `sheet_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `sheet_name` varchar(100) NOT NULL DEFAULT '',
            `sheet_subtitle` text,
            `sheet_description` text,
            `sheet_due_date` date DEFAULT NULL,
            `sheet_bank_account` int(11) unsigned NOT NULL,
            `sheet_type` enum('registrations','orders') NOT NULL DEFAULT 'registrations',
            `sheet_phone` varchar(40) DEFAULT NULL,
            `sheet_mail` varchar(60) DEFAULT NULL,
            PRIMARY KEY (`sheet_id`),
            KEY `sheet_bank_account` (`sheet_bank_account`),
            CONSTRAINT `order_sheets_ibfk_1` FOREIGN KEY (`sheet_bank_account`) REFERENCES `bank_accounts` (`account_id`) ON UPDATE CASCADE
          ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;



        # Dump of table order_users
        # ------------------------------------------------------------

        CREATE TABLE `order_users` (
          `order_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_user_user` int(11) unsigned DEFAULT NULL,
          `order_user_firstname` varchar(40) NOT NULL DEFAULT '',
          `order_user_lastname` varchar(40) NOT NULL DEFAULT '',
          `order_user_mail` varchar(80) DEFAULT NULL,
          `order_user_phone` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`order_user_id`),
          KEY `order_user_user` (`order_user_user`),
          CONSTRAINT `order_users_ibfk_1` FOREIGN KEY (`order_user_user`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table orders
        # ------------------------------------------------------------

        CREATE TABLE `orders` (
          `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `order_price` int(11) NOT NULL,
          `order_payment_method` varchar(40) NOT NULL DEFAULT '',
          `order_created_at` datetime NOT NULL,
          `order_paid_at` datetime DEFAULT NULL,
          `order_user` int(11) unsigned NOT NULL,
          `order_secret` varchar(100) NOT NULL DEFAULT '',
          `order_valid` tinyint(1) NOT NULL,
          `order_failed_at` datetime DEFAULT NULL,
          `order_sheet` int(11) unsigned DEFAULT NULL,
          PRIMARY KEY (`order_id`),
          KEY `order_sheet` (`order_sheet`),
          KEY `order_user` (`order_user`),
          CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`order_sheet`) REFERENCES `order_sheets` (`sheet_id`) ON UPDATE CASCADE,
          CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`order_user`) REFERENCES `order_users` (`order_user_id`) ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table payment_stripe
        # ------------------------------------------------------------

        CREATE TABLE `payment_stripe` (
            `stripe_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `stripe_source` varchar(60) NOT NULL DEFAULT '',
            `stripe_method` varchar(30) NOT NULL DEFAULT '',
            `stripe_order` int(11) unsigned NOT NULL,
            `stripe_bank_account` int(11) unsigned NOT NULL,
            `stripe_status` varchar(40) NOT NULL DEFAULT 'pending',
            `stripe_charge` varchar(60) DEFAULT NULL,
            PRIMARY KEY (`stripe_id`),
            KEY `stripe_order` (`stripe_order`),
            KEY `stripe_bank_account` (`stripe_bank_account`),
            CONSTRAINT `payment_stripe_ibfk_1` FOREIGN KEY (`stripe_order`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `payment_stripe_ibfk_2` FOREIGN KEY (`stripe_bank_account`) REFERENCES `bank_accounts` (`account_id`) ON UPDATE CASCADE
          ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

        # Dump of table product_option_sets
        # ------------------------------------------------------------

        CREATE TABLE `product_option_sets` (
          `set_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `set_name` varchar(30) NOT NULL DEFAULT '',
          `set_product` int(11) unsigned NOT NULL,
          PRIMARY KEY (`set_id`),
          KEY `set_product` (`set_product`),
          CONSTRAINT `product_option_sets_ibfk_1` FOREIGN KEY (`set_product`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table product_options
        # ------------------------------------------------------------

        CREATE TABLE `product_options` (
          `option_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `option_set` int(11) unsigned NOT NULL,
          `option_name` varchar(50) NOT NULL DEFAULT '',
          `option_price_change` int(11) NOT NULL,
          PRIMARY KEY (`option_id`),
          KEY `option_set` (`option_set`),
          CONSTRAINT `product_options_ibfk_1` FOREIGN KEY (`option_set`) REFERENCES `product_option_sets` (`set_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table product_prices
        # ------------------------------------------------------------

        CREATE TABLE `product_prices` (
          `price_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `price_name` varchar(30) CHARACTER SET latin1 NOT NULL DEFAULT '',
          `price_price` int(11) unsigned NOT NULL,
          `price_product` int(11) unsigned NOT NULL,
          PRIMARY KEY (`price_id`),
          KEY `price_product` (`price_product`),
          CONSTRAINT `product_prices_ibfk_1` FOREIGN KEY (`price_product`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



        # Dump of table products
        # ------------------------------------------------------------

        CREATE TABLE `products` (
          `product_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `product_name` varchar(50) NOT NULL DEFAULT '',
          `product_description` text,
          `product_type` enum('unit','person','name') NOT NULL DEFAULT 'unit',
          `product_price_name` varchar(50) DEFAULT NULL,
          PRIMARY KEY (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        ALTER TABLE `events` ADD COLUMN `order_sheet_id` int(11) unsigned DEFAULT NULL;
        ALTER TABLE `events` ADD KEY `order_sheet_id` (`order_sheet_id`);
        ALTER TABLE `events` ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`order_sheet_id`) REFERENCES `order_sheets` (`sheet_id`) ON DELETE CASCADE ON UPDATE CASCADE;

        SET FOREIGN_KEY_CHECKS=1;
        ";

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
