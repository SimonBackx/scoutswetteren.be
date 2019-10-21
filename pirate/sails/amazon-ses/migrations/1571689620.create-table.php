<?php
namespace Pirate\Sails\AmazonSes\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class CreateTable1571689620 extends Migration
{

    public static function upgrade(): bool
    {
        $create_query = "CREATE TABLE `mails` (
            `mail_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `mail_subject` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            `mail_sender` json NOT NULL,
            `mail_recipients` json NOT NULL,
            `mail_reply_to` json NOT NULL,
            `mail_attachments` json NOT NULL,
            `mail_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
            `mail_html` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
            PRIMARY KEY (`mail_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
