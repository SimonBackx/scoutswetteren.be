<?php
namespace Pirate\Sails\Leiding\Migrations;

use Pirate\Sails\Migrations\Classes\Migration;

class MoveToUsers1543768025 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "SELECT * from `leiding`";

        $linking = [];

        if ($result = self::getDb()->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];

                $firstname = self::getDb()->escape_string($row['firstname']);
                $lastname = self::getDb()->escape_string($row['lastname']);
                $mail = self::getDb()->escape_string($row['mail']);

                if (!isset($row['phone'])) {
                    $phone = 'NULL';
                } else {
                    $phone = "'" . self::getDb()->escape_string($row['phone']) . "'";
                }

                if (!isset($row['password'])) {
                    $password = 'NULL';
                } else {
                    $password = "'" . self::getDb()->escape_string($row['password']) . "'";
                }

                if (!isset($row['set_password_key'])) {
                    $set_password_key = 'NULL';
                } else {
                    $set_password_key = "'" . self::getDb()->escape_string($row['set_password_key']) . "'";
                }

                $query = "INSERT INTO
                users (`user_firstname`, `user_lastname`, `user_mail`, `user_phone`, `user_password`, `user_set_password_key`)
                VALUES ('$firstname', '$lastname', '$mail', $phone, $password, $set_password_key)";

                if (!self::getDb()->query($query)) {
                    throw new \Exception(self::getDb()->error);
                }

                $user_id = self::getDb()->insert_id;
                echo "Leiding(id: $id) successfully created User '" . $user_id . "'. Linking ahead.\n";
                $linking[$id] = $user_id;
            }
        }

        echo "\nKlaar. Voor alle Leiding-instanties werd een User aangemaakt.\n";

        $drop_query = "ALTER TABLE leiding
            DROP COLUMN `firstname`,
            DROP COLUMN `lastname`,
            DROP COLUMN `mail`,
            DROP COLUMN `password`,
            DROP COLUMN `set_password_key`,
            DROP COLUMN `phone`
            ;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Dropped columns\n";

        $create_query = "ALTER TABLE leiding
            ADD COLUMN `user_id` int(11) unsigned AFTER `id`,
            ADD CONSTRAINT `fk_leiding_users` FOREIGN KEY (`user_id`) REFERENCES users(`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;";

        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Added column\n";

        foreach ($linking as $leiding_id => $user_id) {
            $query = "UPDATE leiding set `user_id` = '$user_id' WHERE id = '$leiding_id'";
            if (!self::getDb()->query($query)) {
                throw new \Exception(self::getDb()->error);
            }
            echo "Linked Leiding($leiding_id) - User($user_id)\n";
        }

        $drop_query = "ALTER TABLE tokens
            DROP foreign key `tokens_ibfk_1`;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Dropped token constraint\n";

        foreach ($linking as $leiding_id => $user_id) {
            $query = "UPDATE tokens set `client` = '$user_id' WHERE client = '$leiding_id'";
            if (!self::getDb()->query($query)) {
                throw new \Exception(self::getDb()->error);
            }
            echo "Updated token for Leiding($leiding_id) - User($user_id)\n";
        }

        $drop_query = "ALTER TABLE tokens
            ADD CONSTRAINT `fk_tokens_users` FOREIGN KEY (`client`) REFERENCES users(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Added token constraint\n";

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
