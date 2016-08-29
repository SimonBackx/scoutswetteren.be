<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;

class Ouder extends Model {
    public $id;
    private $gezin;
    public $titel;
    public $voornaam;
    public $achternaam;
    public $adres;
    public $postcode;
    public $gemeente;
    public $telefoon;
    public $gsm;
    public $email;
    private $password;

    static $titels = array('Mama', 'Papa', 'Voogd', 'Stiefmoeder', 'Stiefvader');

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];
        $this->gezin = $row['gezin'];
        $this->titel = $row['titel'];
        $this->voornaam = $row['voornaam'];
        $this->achternaam = $row['achternaam'];
        $this->adres = $row['adres'];
        $this->gemeente = $row['gemeente'];
        $this->postcode = $row['postcode'];

        $this->email = $row['email'];
        $this->password = $row['password'];
        $this->gsm = $row['gsm'];
        $this->telefoon = $row['telefoon'];
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $errors = array();

        if (!in_array($data['titel'], self::$titels)) {
            $errors[] = 'Ongeldige titel';
        } else {
            $this->titel = $data['titel'];
        }

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

        Validator::validatePhone($data['gsm'], $this->gsm, $errors);

        if (Validator::isValidMail($data['email'])) {
            $this->email = strtolower($data['email']);
            $data['email'] = $this->email;
        } else {
            $errors[] = 'Ongeldig e-mailadres';
        }


        if (Validator::isValidAddress($data['adres'])) {
            $this->adres = ucwords($data['adres']);
            $data['adres'] = $this->adres;
        } else {
            $errors[] = 'Ongeldig adres';
        }

        Validator::validateGemeente($data['gemeente'], $data['postcode'], $this->gemeente, $this->postcode, $errors);


        if (!empty($data['telefoon'])) {
            Validator::validateNetPhone($data['telefoon'], $this->telefoon, $errors);
        } else {
            $this->telefoon = null;
        }

        return $errors;
    }

    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin->gezin_id;
    }

    function save() {
        if (is_null($this->telefoon)) {
            $telefoon = "NULL";
        } else {
            $telefoon = "'".self::getDb()->escape_string($this->telefoon)."'";
        }

        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $adres = self::getDb()->escape_string($this->adres);
        $gemeente = self::getDb()->escape_string($this->gemeente);
        $postcode = self::getDb()->escape_string($this->postcode);
        $gsm = self::getDb()->escape_string($this->gsm);
        $email = self::getDb()->escape_string($this->email);
        $titel = self::getDb()->escape_string($this->titel);

        if (empty($this->id)) {
            if (empty($this->gezin)) {
                return false;
            }
            $gezin = self::getDb()->escape_string($this->gezin);

            $query = "INSERT INTO 
                ouders (`gezin`, `titel`, `voornaam`, `achternaam`, `adres`, `gemeente`,`postcode`, `gsm`, `email`, `telefoon`)
                VALUES ('$gezin', '$titel', '$voornaam', '$achternaam', '$adres', '$gemeente', '$postcode', '$gsm', '$email', $telefoon)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE ouders 
                SET 
                 `titel` = '$titel',
                 `voornaam` = '$voornaam',
                 `achternaam` = '$achternaam',
                 `adres` = '$adres',
                 `gemeente` = '$gemeente',
                 `postcode` = '$postcode',
                 `email` = '$email',
                 `telefoon` = $telefoon,
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