<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Adres extends Model {
    public $id;

    // Giscode is optioneel (nullable) en wordt enkel gebruikt
    // om de koppeling met de groepsadministratie mogelijk te maken
    public $giscode;
    
    public $gemeente;
    public $straatnaam;
    public $huisnummer; // bv 1A
    public $busnummer; // Altijd nummeriek (optioneel)


    // Later: coordinaten etc

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['adres_id'];

        $this->gemeente = $row['adres_gemeente'];
        $this->straatnaam = $row['adres_straatnaam'];
        $this->huisnummer = $row['adres_huisnummer'];
        $this->busnummer = $row['adres_busnummer'];

        $this->giscode = $row['adres_giscode'];

    }

    function save() {
        $gemeente = self::getDb()->escape_string($this->gemeente);
        $straatnaam = self::getDb()->escape_string($this->straatnaam);
        $huisnummer = self::getDb()->escape_string($this->huisnummer);
        
        if (!isset($this->busnummer)) {
            $busnummer = 'NULL';
        } else {
            $busnummer = "'".self::getDb()->escape_string($this->busnummer)."'";
        }

        if (!isset($this->giscode)) {
            $giscode = 'NULL';
        } else {
            $giscode = "'".self::getDb()->escape_string($this->giscode)."'";
        }

        if (empty($this->id)) {
            $query = "INSERT INTO 
                adressen (`adres_gemeente`,  `adres_straatnaam`, `adres_huisnummer`, `adres_busnummer`, `adres_giscode`)
                VALUES ('$gemeente', '$straatnaam', '$huisnummer', $busnummer, $giscode)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE adressen 
                SET 
                 `adres_gemeente` = '$gemeente',
                 `adres_straatnaam` = '$straatnaam',
                 `adres_huisnummer` = '$huisnummer',
                 `adres_busnummer` = $busnummer
                 `adres_giscode` = $giscode
                 where `adres_id` = '$id' 
            ";
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }
        return false;
    }

}