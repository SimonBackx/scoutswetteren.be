<?php
namespace Pirate\Sails\AmazonSes\Cronjobs;

use Pirate\Sails\AmazonSes\Models\Mail;
use Pirate\Sails\Cache\Classes\CacheHelper;
use Pirate\Wheel\Cronjob;

class Send extends Cronjob
{
    public function needsRunning()
    {
        $synced = CacheHelper::get("isSendingMails");
        return !isset($synced) || !$synced;
    }

    public function run()
    {
        // Pauze cronjob for 10 minutes
        CacheHelper::set("isSendingMails", true, 60 * 10);
        $mails = Mail::getScheduledMails();
        foreach ($mails as $mail) {
            CacheHelper::set("isSendingMails", true, 60 * 10);
            $mail->send();
            $mail->delete();
        }
        CacheHelper::set("isSendingMails", false, 60 * 10);
    }
}
