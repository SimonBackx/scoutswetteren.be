<?php
namespace Pirate\Sails\Leden\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Inschrijving;

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

    function getTotaal() {
        return '€ '.money_format('%!.2n', $this->totaal);
    }
    function getBetaaldScouts() {
        return '€ '.money_format('%!.2n', $this->betaald_scouts);
    }
    
    function getBetaaldOverschrijving() {
        return '€ '.money_format('%!.2n', $this->betaald_overschrijving);
    }

    function getBetaaldCash() {
        return '€ '.money_format('%!.2n', $this->betaald_cash);
    }

    function isBetaald() {
        $sum =  $this->totaal 
            - $this->betaald_cash 
            - $this->betaald_overschrijving 
            - $this->betaald_scouts;
        return 
        $sum < 0.005 && $sum > -0.005;
    }

    function getNogTeBetalenFloat() {
        return $this->totaal - $this->betaald_cash - $this->betaald_overschrijving - $this->betaald_scouts;
    }

    function getNogTeBetalen() {
        $nog = $this->getNogTeBetalenFloat();
        if ($nog < 0) {
           return '- € '.money_format('%!.2n', -$nog); 
        }
        return '€ '.money_format('%!.2n', $nog);
    }

    // True on success
    function betaalMetOverschrijving(string &$bedrag, &$message, &$errors, $cash = false) {
        $out = 0;
        Validator::validatePrice($bedrag, $out, $errors, true);

        if (count($errors) == 0) {
            // Alle te betalen leden overlopen, en gaten zo goed mogelijk proberen op te vullen

            if ($cash) {
                $this->betaald_cash += $out;
            } else {
                $this->betaald_overschrijving += $out;
            }

            $betaald_totaal = $this->betaald_overschrijving + $this->betaald_scouts + $this->betaald_cash;
            $te_veel = $betaald_totaal - $this->totaal;


            // terugstorting
            if ($out < 0) {
                if ($betaald_totaal < 0) {
                    $errors[] = 'Je kan niet meer terugstorten dan er betaald is geweest.';
                    return false;
                }
            }
            
            $okay = $this->save();

            if (!$okay) {
                $errors[] = 'Er ging iets mis bij het registreren van de betaling bij de inschrijvingen';
                return false;
            }

            if ($te_veel > 0) {
                $message = 'Er werd in totaal te veel lidgeld betaald door dit gezin. Gelieve dit te corrigeren en terug te storten en de ouders te verwittigen.';
                
            } elseif ($te_veel == 0){
                $message = 'Hoera, de afrekening is in orde.';
            } else {
                $message = 'Het lidgeld is nog niet volledig betaald door de ouders.';
            }
            return true;
        }

        return false;
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
                    $afrekening->addInschrijving(new Inschrijving($row, null));
                }
                return $afrekening;
            }
        }
        return null;
    }

    // enkel onbetaalde of in huidge scoutsjaar (geordend op onbetaald)
    static function getAfrekeningen() {
        $jaar = self::getDb()->escape_string(Inschrijving::getScoutsjaar());

        $query = '
            SELECT a.*, i.*, l.* from afrekeningen a
                left join inschrijvingen i on i.afrekening = a.afrekening_id
                left join leden l on i.lid = l.id
             where a.oke = 0 or i.scoutsjaar = "'.$jaar.'"
             order by a.oke, a.afrekening_id';

        $afrekeningen = array();
        
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows >= 1){
                $last_id = -1;

                while ($row = $result->fetch_assoc()) {
                    $afrekening = new Afrekening($row);

                    if ($afrekening->id != $last_id) {
                        $last_id = $afrekening->id;
                        $afrekeningen[] = $afrekening;
                    } else {
                        $afrekening = $afrekeningen[count($afrekeningen) - 1];
                    }
                    $afrekening->addInschrijving(new Inschrijving($row, null));
                }
            }
        }
        return $afrekeningen;
    }

    static function getAfrekeningenForGezin(Gezin $gezin) {
        $gezin = self::getDb()->escape_string($gezin->id);

        $query = '
            SELECT a.*, i.*, l.* from afrekeningen a
                left join inschrijvingen i on i.afrekening = a.afrekening_id
                left join leden l on i.lid = l.id
            where a.gezin = "'.$gezin.'" 
            order by i.scoutsjaar desc, a.afrekening_id';

        $afrekeningen = array();
        
        if ($result = self::getDb()->query($query)){
            if ($result->num_rows >= 1){
                $last_id = -1;

                while ($row = $result->fetch_assoc()) {
                    $afrekening = new Afrekening($row);

                    if ($afrekening->id != $last_id) {
                        $last_id = $afrekening->id;
                        $afrekeningen[] = $afrekening;
                    } else {
                        $afrekening = $afrekeningen[count($afrekeningen) - 1];
                    }
                    $afrekening->addInschrijving(new Inschrijving($row, null));
                }
            }
        }

        return $afrekeningen;
    }

    function getMededeling() {
        return mb_strtoupper('Lidgeld '.$this->id.' '.$this->mededeling, 'UTF-8');
    }

    static function createForInschrijvingen($inschrijvingen) {
        $afrekening = new Afrekening();
        $afrekening->inschrijvingen = $inschrijvingen;

        $totaal = 0;
        $gezin = null;
        $ids = array();
        $achternamen = array();
        $betaald_scouts = 0;
        $jaar = -1;

        foreach ($inschrijvingen as $inschrijving) {
            $ids[] = "'".self::getDb()->escape_string($inschrijving->id)."'";
            $totaal += floatval($inschrijving->prijs);
            if (!in_array($inschrijving->lid->achternaam, $achternamen)) {
                $achternamen[] = $inschrijving->lid->achternaam;
            }

            if ($jaar == -1) {
                $jaar = $inschrijving->scoutsjaar;
            }
            if ($jaar != $inschrijving->scoutsjaar) {
                return null;
            }

            if (is_null($gezin)) {
                $gezin = $inschrijving->lid->gezin;
            } else {
                if ($gezin->id != $inschrijving->lid->gezin->id) {
                    // gezinnen verschillend van de inschrijving
                    // onmogelijk!
                    return null;
                }
            }
        }

        if (is_null($gezin)) {
            return null;
        }

        if ($gezin->scouting_op_maat) {
            $betaald_scouts = $totaal;
        }
        $afrekening->betaald_scouts = $betaald_scouts;
        $afrekening->gezin = $gezin;
        $afrekening->totaal = $totaal;

        $mededeling = implode('/', $achternamen);

        if (strlen($mededeling) > 21) {
            foreach ($achternamen as $key => $value) {
                $achternamen[$key] = substr($value, 0, 4);
            }
            $mededeling = substr(implode('/', $achternamen), 0, 21);
        }

        $oke = 0;
        if ($afrekening->isBetaald()) {
            $oke = 1;
        }

        $mededeling = self::getDb()->escape_string($mededeling);
        $betaald_scouts = self::getDb()->escape_string($betaald_scouts);
        $gezin = self::getDb()->escape_string($gezin->id);
        $totaal = self::getDb()->escape_string($totaal);
        
        $query = "INSERT INTO 
                afrekeningen (`mededeling`, `betaald_scouts`, `totaal`,  `gezin`, `oke`)
                VALUES ('$mededeling', '$betaald_scouts', '$totaal', '$gezin', '$oke')";

        self::getDb()->autocommit(false);

        if (self::getDb()->query($query)) {
            $afrekening->id = self::getDb()->insert_id;
            $afrekening_id = self::getDb()->escape_string($afrekening->id);


            $ids = implode(', ', $ids);
            $query = "UPDATE inschrijvingen 
                SET 
                 `afrekening` = '$afrekening_id',
                 `afrekening_oke` = '$oke'
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

    function recalculate() {
        $this->totaal = 0;
        foreach ($this->inschrijvingen as $inschrijving) {
           $this->totaal += floatval($inschrijving->prijs);
        }
        return $this->save();
    }

    function save() {
        if (!isset($this->id)) {
            self::getDb()->autocommit(true); // nodig voor aanroep in inschrijving->save()
            return false;
        }

        $id = self::getDb()->escape_string($this->id);

        $betaald_scouts = self::getDb()->escape_string($this->betaald_scouts);
        $betaald_overschrijving = self::getDb()->escape_string($this->betaald_overschrijving);
        $betaald_cash = self::getDb()->escape_string($this->betaald_cash);
        $totaal = self::getDb()->escape_string($this->totaal);

        $oke = 0;
        if ($this->isBetaald()) {
            $oke = 1;
        }

        $query = "UPDATE afrekeningen 
                SET 
                 `betaald_cash` = '$betaald_cash',
                 `betaald_overschrijving` = '$betaald_overschrijving',
                 `betaald_scouts` = '$betaald_scouts',
                 `totaal` = '$totaal',
                 `oke` = $oke
                 where `afrekening_id` = '$id'
            ";

        self::getDb()->autocommit(false);
        if (!self::getDb()->query($query)) {
             self::getDb()->autocommit(true);
            return false;
        }


        $query = "UPDATE inschrijvingen 
                SET 
                 `afrekening_oke` = $oke
                 where `afrekening` = '$id'
            ";

        if (!self::getDb()->query($query)) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);
            return false;
        }

        self::getDb()->commit();
        self::getDb()->autocommit(true);
        return true;
    }

}