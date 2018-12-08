<?php
namespace Pirate\Classes\Users;
use Pirate\Classes\Migrations\Migration;
use Pirate\Model\Users\User;

class RemoveDuplicates1544289883 extends Migration {

    static function upgrade(): bool {
        $query = "ALTER TABLE `users` MODIFY `user_mail` varchar(100) null;";

        if (!self::getDb()->query($query)) {

            echo "Failed adding unique index on users table\n";
            return false;
        }
        echo "Made mail nullable on users table\n";

        $query = "SELECT * from `users`";

        $linking = [];

        if ($result = self::getDb()->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $user = new User($row);
                
                if (isset($linking[strtolower($user->mail)])) {
                    $other = $linking[strtolower($user->mail)];

                    // duplicate found
                    if (!$other->hasPassword() && $user->hasPassword()) {
                        // delete other
                        $other->mail = null;
                        $other->save();

                        // todo: send an email
                        echo "Deleted duplicate email address for User($other->id)\n";

                        $linking[strtolower($user->mail)] = $user;
                    } else {
                        // delete other
                        $user->mail = null;
                        $user->save();

                        // todo: send an email

                        echo "Deleted duplicate email address for User($user->id)\n";
                    }
                } else {
                    $linking[strtolower($user->mail)] = $user;
                }
            }
        }

        $query = "ALTER TABLE `users` ADD CONSTRAINT `unique_mail` UNIQUE (`user_mail`)";

        if ($result = self::getDb()->query($query)) {

            echo "Added unique index on users table\n";
            return true;
        }
        echo "Failed adding unique index on users table\n";

        throw new \Exception(self::getDb()->error);
        return false;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}