<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;

class MoveToUsers1544282142 extends Migration {

    static function upgrade(): bool {
        $query = "SELECT * from `ouders`";

        $linking = [];

        if ($result = self::getDb()->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];

                $firstname = self::getDb()->escape_string($row['voornaam']);
                $lastname = self::getDb()->escape_string($row['achternaam']);
                $mail = self::getDb()->escape_string($row['email']);

                if (!isset($row['gsm'])) {
                    $phone = 'NULL';
                } else {
                    $phone = "'".self::getDb()->escape_string($row['gsm'])."'";
                }

                if (!isset($row['password'])) {
                    $password = 'NULL';
                } else {
                    $password = "'".self::getDb()->escape_string($row['password'])."'";
                }

                if (!isset($row['set_password_key'])) {
                    $set_password_key = 'NULL';
                } else {
                    $set_password_key = "'".self::getDb()->escape_string($row['set_password_key'])."'";
                }

                // todo: check duplicates of e-mail and prefill with a temporary email

                $query = "INSERT INTO 
                users (`user_firstname`, `user_lastname`, `user_mail`, `user_phone`, `user_password`, `user_set_password_key`)
                VALUES ('$firstname', '$lastname', '$mail', $phone, $password, $set_password_key)";


                if (!self::getDb()->query($query)) {
                    throw new \Exception(self::getDb()->error);
                }

                $user_id = self::getDb()->insert_id;
                echo "Ouder(id: $id) successfully created User '".$user_id ."'. Linking ahead.\n";
                $linking[$id] = $user_id;
            }
        }

        echo "\nKlaar. Voor alle Leiding-instanties werd een User aangemaakt.\n";

        $drop_query = "ALTER TABLE ouders
            DROP COLUMN `voornaam`,
            DROP COLUMN `achternaam`,
            DROP COLUMN `email`,
            DROP COLUMN `password`,
            DROP COLUMN `set_password_key`,
            DROP COLUMN `gsm`
            ;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Dropped columns\n";

        $create_query = "ALTER TABLE ouders
            ADD COLUMN `user_id` int(11) unsigned AFTER `id`,
            ADD CONSTRAINT `fk_ouders_users` FOREIGN KEY (`user_id`) REFERENCES users(`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;";
        
        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }
        echo "Added column user_id to ouders\n";

        $drop_query = "ALTER TABLE ouder_magic_tokens
            DROP foreign key `ouder_magic_tokens_ibfk_1`;";
        
        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }
        echo "Dropped foreign key of ouder_magic_tokens\n";
        
        $query = "RENAME TABLE ouder_magic_tokens TO user_magic_tokens;";
        
        if (!self::getDb()->query($query)) {
            throw new \Exception(self::getDb()->error);
        }
        echo "Renamed ouder_magic_tokens to user_magic_tokens\n";

        foreach ($linking as $ouder_id => $user_id) {
            $query = "UPDATE ouders set `user_id` = '$user_id' WHERE id = '$ouder_id'";
            if (!self::getDb()->query($query)) {
                throw new \Exception(self::getDb()->error);
            }
            echo "Linked Ouder($ouder_id) - User($user_id)\n";

            // Edit magic tokens
            $query = "UPDATE user_magic_tokens set `client` = '$user_id' WHERE client = '$ouder_id'";
            if (!self::getDb()->query($query)) {
                throw new \Exception(self::getDb()->error);
            }
            echo "Updated magic tokens of Ouder($ouder_id) to User($user_id)\n";
        }

        $query = "ALTER TABLE user_magic_tokens
            ADD CONSTRAINT `fk_users` FOREIGN KEY (`client`) REFERENCES users(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;";

        if (!self::getDb()->query($query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Added user_magic_tokens foreign key\n";

        $drop_query = "DROP TABLE ouder_tokens;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Dropped table ouder_tokens. All tokens are removed\n";

        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }
}