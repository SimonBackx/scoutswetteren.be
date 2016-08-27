<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;

class Lid extends Model {
    public $id;
    private $gezin;
    public $voornaam;
    public $achternaam;
    public $geslacht;
    public $geboortedatum;
    public $gsm;
    private $steekkaart;


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
        $this->steekkaart = $row['steekkaart'];
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

    function save(){
        $id = self::getDb()->escape_string($this->id);
        $firstname = self::getDb()->escape_string($this->firstname);
        $lastname = self::getDb()->escape_string($this->lastname);
        $totem = self::getDb()->escape_string($this->totem);
        $mail = self::getDb()->escape_string($this->mail);
        $phone = self::getDb()->escape_string($this->phone);

        $query = "UPDATE leiding 
            SET 
             firstname = '$firstname',
             lastname = '$lastname',
             totem = '$totem',
             mail = '$mail',
             phone = '$phone'
             where id = '$id' 
        ";

        return self::getDb()->query($query);
    }


    
}