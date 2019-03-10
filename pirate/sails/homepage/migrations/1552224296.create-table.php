<?php
namespace Pirate\Classes\Homepage;

use Pirate\Classes\Migrations\Migration;

class CreateTable1552224296 extends Migration
{

    public static function upgrade(): bool
    {
        $query = "DROP TABLE IF EXISTS `slideshows`;

        CREATE TABLE `slideshows` (
          `slideshow_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `slideshow_title` text NOT NULL,
          `slideshow_text` text NOT NULL,
          `slideshow_priority` int(11) NOT NULL,
          `slideshow_button_text` varchar(64) DEFAULT NULL,
          `slideshow_button_url` varchar(128) DEFAULT NULL,
          `slideshow_extra_button_text` varchar(64) DEFAULT NULL,
          `slideshow_extra_button_url` varchar(128) DEFAULT NULL,
          PRIMARY KEY (`slideshow_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

        LOCK TABLES `slideshows` WRITE;
        /*!40000 ALTER TABLE `slideshows` DISABLE KEYS */;

        INSERT INTO `slideshows` (`slideshow_id`, `slideshow_title`, `slideshow_text`, `slideshow_priority`, `slideshow_button_text`, `slideshow_button_url`, `slideshow_extra_button_text`, `slideshow_extra_button_url`)
        VALUES
            (3,'Wij zoeken wafelbakkers!','Op 31/03 organiseren we onze wafelbak. Daarvoor zijn we nog op zoek naar enthousiaste bakkers! Wil jij graag wafeltjes bakken? Laat het ons weten via wafels@scoutswetteren.be',2,'Meer info','https://files.scoutswetteren.be/download/wafelbak-algemeen-2019.pdf',NULL,NULL);

        /*!40000 ALTER TABLE `slideshows` ENABLE KEYS */;
        UNLOCK TABLES;";

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
