<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Afrekening extends Model {
    public $id;
    public $gezin; //id
    public $betaald_cash;
    public $betaald_scouts;
    public $betaald_overschrijving;
    public $totaal;
    private $mededeling;

    public $inschrijvingen; // array van inschrijving objecten

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['afrekening_id'];

        $this->gezin = $row['gezin'];
        $this->betaald_cash = $row['betaald_cash'];
        $this->betaald_scouts = $row['betaald_scouts'];
        $this->betaald_overschrijving = $row['betaald_overschrijving'];
        $this->totaal = $row['totaal'];
        $this->mededeling = $row['mededeling'];

        $this->inschrijvingen = array();
    }

    function getTeBetalen() {
        return '€ '.money_format('%!.2n', $this->totaal - $this->betaald_scouts);
    }
    function getBetaaldScouts() {
        return '€ '.money_format('%!.2n', $this->betaald_scouts);
    }

    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin->id;
    }

    function addInschrijving(Inschrijving $inschrijving) {
        $this->inschrijvingen[] = $inschrijving;
    }

    static function getAfrekening($id) {
        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT a.*, i.*, l.* from afrekeningen a
                left join inschrijvingen i on i.afrekening = a.afrekening_id
                left join leden l on i.lid = l.id
            where a.afrekening_id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows >= 1){
                $row = $result->fetch_assoc();
                $afrekening = new Afrekening($row);
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()) {
                    $afrekening->addInschrijving(new Inschrijving($row));
                }
                return $afrekening;
            }
        }
        return null;
    }

    function getMededeling() {
        return strtoupper('Lidgeld '.$this->id.' '.$this->mededeling);
    }

    static function createForInschrijvingen($inschrijvingen) {
        $afrekening = new Afrekening();
        $afrekening->inschrijvingen = $inschrijvingen;

        $totaal = 0;
        $gezin = null;
        $ids = array();
        $achternamen = array();
        $betaald_scouts = 0;

        foreach ($inschrijvingen as $inschrijving) {
            $ids[] = "'".self::getDb()->escape_string($inschrijving->id)."'";
            $totaal += floatval($inschrijving->prijs);
            if (!in_array($inschrijving->lid->achternaam, $achternamen)) {
                $achternamen[] = $inschrijving->lid->achternaam;
            }
            $betaald_scouts += $inschrijving->betaald_door_scouts;

            if (is_null($gezin)) {
                $gezin = $inschrijving->lid->gezin->id;
            } else {
                if ($gezin != $inschrijving->lid->gezin->id) {
                    // gezinnen verschillend van de inschrijving
                    // onmogelijk!
                    return null;
                }
            }
        }

        $mededeling = implode('/', $achternamen);

        if (strlen($mededeling) > 21) {
            foreach ($achternamen as $key => $value) {
                $achternamen[$key] = substr($value, 0, 4);
            }
            $mededeling = substr(implode('/', $achternamen), 0, 21);
        }

        $mededeling = self::getDb()->escape_string($mededeling);
        $betaald_scouts = self::getDb()->escape_string($betaald_scouts);

        $afrekening->gezin = $gezin;
        $gezin = self::getDb()->escape_string($gezin);
        $afrekening->totaal = $totaal;
        
        $query = "INSERT INTO 
                afrekeningen (`mededeling`, `betaald_scouts`, `totaal`,  `gezin`)
                VALUES ('$mededeling', '$betaald_scouts', '$totaal', '$gezin')";

        self::getDb()->autocommit(false);

        if (self::getDb()->query($query)) {
            $afrekening->id = self::getDb()->insert_id;
            $afrekening_id = self::getDb()->escape_string($afrekening->id);

            $ids = implode(', ', $ids);
            $query = "UPDATE inschrijvingen 
                SET 
                 `afrekening` = '$afrekening_id'
                 where `inschrijving_id` IN ($ids)
            ";
            if (!self::getDb()->query($query)) {
                echo self::getDb()->error;
                self::getDb()->rollback();
                self::getDb()->autocommit(true);
                return null;
            }
            self::getDb()->commit();
            self::getDb()->autocommit(true);
            return $afrekening;
        }

        self::getDb()->autocommit(true);

        return null;
    }

}