<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;

class Adres1529781209 extends Migration {

    static function upgrade(): bool {
        $query = "CREATE TABLE `adressen` (
            `adres_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `adres_straatnaam` varchar(120) NOT NULL DEFAULT '',

            `adres_gemeente` varchar(50) NOT NULL DEFAULT '',
            `adres_postcode` int(4) NOT NULL,
            `adres_huisnummer` varchar(15) NOT NULL DEFAULT '',
            `adres_busnummer` varchar(15) DEFAULT NULL,
            `adres_voluit` varchar(200) NOT NULL DEFAULT '',

            `adres_giscode` varchar(10) DEFAULT NULL,

            `adres_longitude` decimal(11,4) DEFAULT NULL,
            `adres_latitude` decimal(11,4) DEFAULT NULL,
            `adres_telefoon` varchar(30) DEFAULT NULL,

            PRIMARY KEY (`adres_id`),
            UNIQUE KEY `adres_voluit` (`adres_voluit`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if ($result = self::getDb()->query($query)) {
            return true;
        }
        
        throw new \Exception(self::getDb()->error);
    }

    static function downgrade(): bool {
        $query = "DROP TABLE `adressen`";
        if ($result = self::getDb()->query($query)) {
            return true;
        }
        
        throw new \Exception(self::getDb()->error);
    }

}