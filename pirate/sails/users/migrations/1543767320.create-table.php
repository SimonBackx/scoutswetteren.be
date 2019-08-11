<?php
namespace Pirate\Sails\Users\Classes;
use Pirate\Sails\Migrations\Classes\Migration;

class CreateTable1543767320 extends Migration {

    static function upgrade(): bool {
        $query = "CREATE TABLE `users` (
            `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `user_mail` varchar(100) NOT NULL DEFAULT '',
            `user_firstname` varchar(50) NOT NULL DEFAULT '',
            `user_lastname` varchar(50) NOT NULL DEFAULT '',
            `user_phone` varchar(50) DEFAULT NULL,
            `user_password` varchar(60) DEFAULT NULL,
            `user_set_password_key` varchar(60) DEFAULT NULL,
            PRIMARY KEY (`user_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if ($result = self::getDb()->query($query)) {
            return true;
        }

        return false;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}