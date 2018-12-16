<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;
use Pirate\Model\Leden\Lid;

class SearchTable1544972586 extends Migration {

    static function upgrade(): bool {
        $query = "CREATE TABLE `leden_search` (
            `search_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `search_lid` int(11) unsigned NOT NULL,
            `search_text` text NOT NULL,
            PRIMARY KEY (`search_id`),
            UNIQUE KEY `search_lid` (`search_lid`),
            FULLTEXT KEY `search_text` (`search_text`),
            CONSTRAINT `leden_search_ibfk_1` FOREIGN KEY (`search_lid`) REFERENCES `leden` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if ($result = self::getDb()->query($query)) {
            // Loop all
            $leden = Lid::getLedenFull();
            foreach ($leden as $lid) {
                $lid->updateSearchIndex();
            }
            return true;
        }
        
        throw new \Exception(self::getDb()->error);
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}