<?php
namespace Pirate\Sails\Blog\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class AuthorEditorConstraints1575721020 extends Migration
{

    public static function upgrade(): bool
    {

        $create_query = "ALTER TABLE articles
        DROP FOREIGN KEY `articles_ibfk_1`,
        DROP FOREIGN KEY `articles_ibfk_2`;";

        // Todo: foreign key toevoegen

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        $create_query = "ALTER TABLE articles
         ADD CONSTRAINT `articles_users_1` FOREIGN KEY (`author`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
         ADD CONSTRAINT `articles_users_2` FOREIGN KEY (`editor`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE";

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
