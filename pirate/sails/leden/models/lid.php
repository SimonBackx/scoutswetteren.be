<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;

class Lid extends Model {
    public $id;
    private $gezin;
    public $voornaam;
    public $achternaam;
    public $geslacht;
    public $geboortedatum;
    public $gsm;


    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];
        $this->gezin = $row['gezin'];
        $this->voornaam = $row['voornaam'];
        $this->achternaam = $row['achternaam'];
        $this->geslacht = $row['geslacht'];
        $this->geboortedatum = new \DateTime($row['geboortedatum']);
        $this->gsm = $row['gsm'];
    }

    static function getScoutsjaar() {
        $jaar = intval(date('Y'));
        $maand = intval(date('n'));
        if ($maand >= 9) {
            return $jaar;
        } else {
            return $jaar - 1;
        }
    }

    static function getTakkenVerdeling($scoutsjaar) {
        return array(
                 $scoutsjaar - 7 => 'kapoenen', $scoutsjaar - 6 => 'kapoenen',
                 $scoutsjaar - 8 => 'wouters', $scoutsjaar - 9 => 'wouters', $scoutsjaar - 10 => 'wouters', 
                 $scoutsjaar - 11 => 'jonggivers', $scoutsjaar - 12 => 'jonggivers', $scoutsjaar - 13 => 'jonggivers',
                 $scoutsjaar - 14 => 'givers', $scoutsjaar - 15 => 'givers', $scoutsjaar - 16 => 'givers',
                 $scoutsjaar - 17 => 'jin'
             );
    }

    static function getTak($geboortejaar) {
        $verdeling = self::getTakkenVerdeling(self::getScoutsjaar());
        if (isset($verdeling[$geboortejaar])) {
            return $verdeling[$geboortejaar];
        }
        return false;
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $errors = array();

        if (Validator::isValidFirstname($data['voornaam'])) {
            $this->voornaam = ucwords($data['voornaam']);
            $data['voornaam'] = $this->voornaam;
        } else {
            $errors[] = 'Ongeldige voornaam';
        }

        if (Validator::isValidLastname($data['achternaam'])) {
            $this->achternaam = ucwords($data['achternaam']);
            $data['achternaam'] = $this->achternaam;
        } else {
            $errors[] = 'Ongeldige achternaam';
        }

        if ($data['geslacht'] == 'M' || $data['geslacht'] == 'V') {
            $this->geslacht = $data['geslacht'];
        } else {
            $errors[] = 'Geen geslacht geselecteerd';
        }

        $geboortedatum = $data['geboortedatum_jaar'].'-'.$data['geboortedatum_maand'].'-'.$data['geboortedatum_dag'];
        $geboortedatum = \DateTime::createFromFormat('Y-n-j', $geboortedatum);
        if ($geboortedatum !== false && checkdate($data['geboortedatum_maand'], $data['geboortedatum_dag'], $data['geboortedatum_jaar'])) {
            $this->geboortedatum = clone $geboortedatum;
            $data['geboortedatum_dag'] = $geboortedatum->format('j');
            $data['geboortedatum_maand'] = $geboortedatum->format('n');
            $data['geboortedatum_jaar'] = $geboortedatum->format('Y');
        } else {
            $errors[] = 'Ongeldige geboortedatum';
        }

        if (is_numeric($data['geboortedatum_jaar'])) {
            $tak = self::getTak($data['geboortedatum_jaar']);

            if ($tak === false) {
                $errors[] = 'Uw zoon is te oud  / jong voor de scouts. Kinderen zijn toegelaten vanaf 6 jaar.';
            } else {
                $data['tak'] = $tak;

                if ($tak == 'givers' || $tak == 'jin') {
                    Validator::validatePhone($data['gsm'], $this->gsm, $errors);
                }
            }
        }

        return $errors;
    }
    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin->gezin_id;
    }

    function save() {
        if (is_null($this->gsm)) {
            $gsm = "NULL";
        } else {
            $gsm = "'".self::getDb()->escape_string($this->gsm)."'";
        }


        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $geslacht = self::getDb()->escape_string($this->geslacht);
        $geboortedatum = self::getDb()->escape_string($this->geboortedatum->format('Y-m-d'));

        if (empty($this->id)) {
            if (empty($this->gezin)) {
                return false;
            }
            $gezin = self::getDb()->escape_string($this->gezin);

            $query = "INSERT INTO 
                leden (`gezin`,  `voornaam`, `achternaam`, `geslacht`, `geboortedatum`, `gsm`)
                VALUES ('$gezin', '$voornaam', '$achternaam', '$geslacht', '$geboortedatum', $gsm)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE leden 
                SET 
                 `voornaam` = '$voornaam',
                 `achternaam` = '$achternaam',
                 `geslacht` = '$geslacht',
                 `geboortedatum` = '$geboortedatum',
                 `gsm` = $gsm
                 where id = '$id' 
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