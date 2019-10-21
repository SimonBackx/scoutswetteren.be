<?php
namespace Pirate\Sails\Users\Migrations;

use Pirate\Sails\AmazonSes\Models\Mail;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Migrations\Classes\Migration;
use Pirate\Sails\Users\Models\User;

class RemoveDuplicates1544289883 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "UPDATE users set user_mail = 'verhuur@scoutswetteren.be' where user_mail = 'divitaeconsulting@icloud.com'";

        if (!self::getDb()->query($query)) {
            echo "Failed fixing duplicate phone early on by setting same email\n";
            throw new \Exception(self::getDb()->error);
        }

        $query = "UPDATE users set user_mail = 'ellenjooris1@outlook.com' where user_phone = '+32 497 64 53 94'";

        if (!self::getDb()->query($query)) {
            echo "Failed fixing duplicate phone 2 early on by setting same email\n";
            throw new \Exception(self::getDb()->error);
        }

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
                    $clear_phone = (preg_replace('/\s+/', '', $other->phone) == preg_replace('/\s+/', '', $user->phone));
                    $ouder1 = Ouder::getByUserId($other->id);
                    $ouder2 = Ouder::getByUserId($user->id);

                    /// Merge if same phone
                    if ($user->isProbablyEqual($other)) {
                        echo "Merging users $other->id and $user->id\n";

                        if (isset($ouder1)) {
                            if (isset($ouder2)) {
                                if ($ouder1->gezin->id == $ouder2->gezin->id) {
                                    echo "Same gezin, same mail, same phone, but different persons: $other->firstname and $user->firstname! Not merging users.\n";
                                    /// Still send mail
                                } else {
                                    echo "Merging gezinnen\n";
                                    if ($ouder1->gezin->id > $ouder2->gezin->id) {
                                        if (!$ouder1->gezin->merge($ouder2->gezin)) {
                                            echo "!!! failed\n";
                                        }
                                    } else {
                                        if (!$ouder2->gezin->merge($ouder1->gezin)) {
                                            echo "!!! failed\n";
                                        }
                                    }

                                    // Duplicate users have been deleted (no need to empty user data)
                                    continue;
                                }
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

                    $kept = null;
                    $cleared = null;

                    if (!$user->isProbablyEqual($other)) {
                        // Twee verschillende ouders met zelfde e-mailadres => probeer degene te houden van degene wie het e-mailadres is
                        $parts_user = explode(' ', trim($user->lastname));
                        $parts_user = [mb_strtolower($user->firstname), mb_strtolower($parts_user[count($parts_user) - 1])];
                        $parts_other = explode(' ', trim($other->lastname));
                        $parts_other = [mb_strtolower($other->firstname), mb_strtolower($parts_other[count($parts_other) - 1])];

                        foreach ($parts_user as $part) {
                            if (strpos(mb_strtolower(clean_special_chars($user->mail)), $part) !== false) {
                                $kept = $user;
                                $cleared = $other;
                                break;
                            }
                        }

                        foreach ($parts_other as $part) {
                            if (strpos(mb_strtolower(clean_special_chars($user->mail)), $part) !== false) {
                                if (isset($kept)) {
                                    // Both contains: do not prefer anyone!
                                    $kept = null;
                                    $cleared = null;

                                    echo "Both contains!\n";
                                    break;
                                }
                                $kept = $other;
                                $cleared = $user;
                                break;
                            }
                        }
                    }

                    if (!isset($kept, $cleared)) {
                        if (!$other->hasPassword() && $user->hasPassword() || (!($other->hasPassword() && !$user->hasPassword()) && isset($ouder1, $ouder2) && (!$ouder1->isStillActive() && $ouder2->isStillActive()))) {
                            // delete other
                            $kept = $user;
                            $cleared = $other;

                        } else {
                            $kept = $other;
                            $cleared = $user;
                        }
                    }

                    // delete other
                    $cleared->mail = null;
                    if ($clear_phone) {
                        $cleared->phone = null;
                    }
                    $cleared->save();
                    $linking[strtolower($kept->mail)] = $kept;

                    // If kept has no password, but cleared has: move password
                    if (!$kept->hasPassword() && $cleared->hasPassword()) {
                        $kept->setPasswordToUser($cleared);
                    }

                    echo "Deleted duplicate email address for User($cleared->id), keeping $kept->id\n";

                    // CHECK OF DE OUDERS WEL NOG STEEDS ACTIEF ZIJN => ANDERS GEEN MAIL STUREN
                    if (isset($ouder1) && !$ouder1->isStillActive() && isset($ouder2) && !$ouder2->isStillActive()) {
                        echo "Sending no mail: two old accounts\n";
                        continue;
                    }

                    User::createMagicTokensFor([$kept]);
                    $mail = Mail::create('BELANGRIJK: Dubbel e-mailadres niet langer toegestaan', 'user-duplicate-email', array('user' => $kept, 'cleared' => $cleared));
                    $mail->addTo(
                        $kept->mail,
                        array(),
                        $kept->firstname . ' ' . $kept->lastname
                    );
                    $mail->sendOrDelay();

                } else {
                    $linking[strtolower($user->mail)] = $user;
                }
            }
        } else {
            throw new \Exception(self::getDb()->error);
        }

        $query = "ALTER TABLE `users` ADD CONSTRAINT `unique_mail` UNIQUE (`user_mail`)";

        if (!self::getDb()->query($query)) {
            echo "Failed adding unique mail index on users table\n";
            throw new \Exception(self::getDb()->error);
        }
        echo "Added unique mail index on users table\n";

        $query = "ALTER TABLE `users` ADD CONSTRAINT `unique_phone` UNIQUE (`user_phone`)";

        if (!self::getDb()->query($query)) {
            echo "Failed adding unique phone index on users table\n";
            throw new \Exception(self::getDb()->error);
        }
        echo "Added unique phone index on users table\n";

        return true;
    }

    public static function downgrade(): bool
    {
        throw new \Exception("Migration downgrade is not implemented");
    }

}
