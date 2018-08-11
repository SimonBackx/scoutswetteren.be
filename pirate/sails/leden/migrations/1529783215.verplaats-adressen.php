<?php
namespace Pirate\Classes\Leden;
use Pirate\Classes\Migrations\Migration;
use Pirate\Model\Leden\Adres;

class VerplaatsAdressen1529783215 extends Migration {

    static function upgrade(): bool {
        $query = "SELECT * from `ouders`";

        $linking = [];

        if ($result = self::getDb()->query($query)) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $adres = $row['adres'];
                $gemeente = $row['gemeente'];
                $postcode = $row['postcode'];
                $telefoon = $row['telefoon'];

                $errors = [];
                $model = Adres::find($adres, $gemeente, $postcode, $telefoon, $errors);
                if (!isset($model)) {
                    echo implode("\n", $errors)."\n";
                    echo "Please correct Adres of Ouder(id: $id) and try again\n";
                    return false;
                } else {
                    echo "Ouder(id: $id) successfully created Adres '".$model->toString() ."'. Linking ahead.\n";
                    $linking[$id] = $model->id;
                }

            }
        }

        echo "\nKlaar. Voor alle Ouder-instanties werd een Adres aangemaakt.\n";



        $drop_query = "ALTER TABLE ouders
            DROP COLUMN `adres`,
            DROP COLUMN `gemeente`,
            DROP COLUMN `telefoon`,
            DROP COLUMN `postcode`
            ;";

        if (!self::getDb()->query($drop_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Dropped columns\n";

        $create_query = "ALTER TABLE ouders
            ADD COLUMN `adres` int(11) unsigned AFTER `achternaam`,
            ADD CONSTRAINT `fk_ouder_adres` FOREIGN KEY (`adres`) REFERENCES adressen(`adres_id`) ON DELETE RESTRICT ON UPDATE CASCADE;";
        
        if (!self::getDb()->query($create_query)) {
            throw new \Exception(self::getDb()->error);
        }

        echo "Added column\n";

        foreach ($linking as $ouder => $adres) {
            $query = "UPDATE ouders set adres = '$adres' WHERE id = '$ouder'";
            if (!self::getDb()->query($query)) {
                throw new \Exception(self::getDb()->error);
            }
            echo "Linked Ouder($ouder) - Adres($adres)\n";
        }

        return true;
    }

    static function downgrade(): bool {
        throw new \Exception("Migration downgrade is not implemented");
    }

}