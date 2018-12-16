<?php
namespace Pirate\Classes\Users;
use Pirate\Classes\Migrations\Migration;
use Pirate\Model\Users\User;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leiding\Leiding;
use Pirate\Mail\Mail;

class RemoveDuplicates1544289883 extends Migration {

    static function upgrade(): bool {
        $query = "ALTER TABLE `users` MODIFY `user_mail` varchar(100) null;";

        if (!self::getDb()->query($query)) {

            echo "Failed maing users user_mail nullable\n";
            return false;
        }
        echo "Made mail nullable on users table\n";

        /// Sorteer op dubbele phone (zodat we eerst mergen indien mogelijk)
        $query = 
        "SELECT a.* from `users` a
        left join `users` b on a.user_phone = b.user_phone and a.user_mail = b.user_mail
        group by a.user_id
        order by count(b.user_id) desc";

        $linking = [];

        if ($result = self::getDb()->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $user = new User($row);

                if (empty($user->mail)) {
                    $user->mail = null;
                    $user->save();
                    continue;
                }
                
                if (isset($linking[strtolower($user->mail)])) {
                    $other = $linking[strtolower($user->mail)];

                    /// Merge if same phone
                    if (strtolower($other->phone) == strtolower($user->phone)) {
                        echo "Merging users $other->id and $user->id\n";

                        $ouder1 = Ouder::getByUserId($other->id);
                        $ouder2 = Ouder::getByUserId($user->id);

                        if (isset($ouder1)) {
                            if (isset($ouder2)) {
                                echo "!!! Manueel nakijken!\n";
                            } else {
                                $ouder1->user = $user;
                                if (!$ouder1->save()) {
                                    throw new \Exception(self::getDb()->error);
                                }
                                if (!$other->delete()) {
                                    throw new \Exception(self::getDb()->error);
                                }
                                $linking[strtolower($user->mail)] = $user;
                                continue;
                            }

                        } elseif (isset($ouder2)) {
                            $ouder2->user = $other;
                            if (!$ouder2->save()) {
                                throw new \Exception(self::getDb()->error);
                            }
                            if (!$user->delete()) {
                                throw new \Exception(self::getDb()->error);
                            }
                            continue;
                        } else {

                            throw new \Exception("Cannot merge duplicate leiding!");
                        }

                    }

                    $kept = $user;
                    $cleared = $other;

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

                        $kept = $other;
                        $cleared = $user;

                        // todo: send an email

                        echo "Deleted duplicate email address for User($user->id)\n";
                    }

                    // TODO: CHECK OF DE OUDERS WEL NOG STEEDS ACTIEF ZIJN => ANDERS GEEN MAIL STUREN
                    // CHECK OF ER GEEN DUBBELE ONZIN IS ZOALS BIJ DE MISLUKTE MERGE HIERBOVEN! 

                    User::createMagicTokensFor([$kept]);
                    $mail = new Mail('BELANGRIJK: Dubbel e-mailadres niet langer toegestaan', 'user-duplicate-email', array('user' => $kept, 'cleared' => $cleared));
                    $mail->addTo(
                        $kept->mail, 
                        array(),
                        $kept->firstname.' '.$kept->lastname
                    );
                    $mail->send();

                } else {
                    $linking[strtolower($user->mail)] = $user;
                }
            }
        } else {
            throw new \Exception(self::getDb()->error);
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