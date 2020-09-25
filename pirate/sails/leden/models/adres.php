<?php
namespace Pirate\Sails\Leden\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Inschrijving;

use Pirate\Sails\Cache\Classes\CacheHelper;

use Pirate\Wheel\Curl\Curl;
use Pirate\Wheel\Curl\Method;
use Pirate\Wheel\Curl\DataType;

class Adres extends Model {
    public $id;

    // Giscode is optioneel (nullable) en wordt enkel gebruikt
    // om de koppeling met de groepsadministratie mogelijk te maken
    public $giscode; // deprecated
    
    public $gemeente;
    public $postcode;
    public $straatnaam;
    public $huisnummer; // bv 1A
    public $busnummer; // Altijd nummeriek (optioneel)

    public $telefoon; // Optional

    public $latitude;
    public $longitude;

    // Later: coordinaten etc

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['adres_id'];

        $this->gemeente = $row['adres_gemeente'];
        $this->postcode = $row['adres_postcode'];
        $this->straatnaam = $row['adres_straatnaam'];
        $this->huisnummer = $row['adres_huisnummer'];
        $this->busnummer = $row['adres_busnummer'];
        
        $this->giscode = $row['adres_giscode'];

        $this->telefoon = $row['adres_telefoon'];
        $this->latitude = $row['adres_latitude'];
        $this->longitude = $row['adres_longitude'];

    }

    function getAdres() {
        return $this->straatnaam.' '.$this->huisnummer. (isset($this->busnummer) ? ' bus '.$this->busnummer : '');
    }

    function toString() {
        return $this->getAdres().', '.$this->postcode.' '.$this->gemeente;
    }

    function findDuplicate() {
        $voluit = self::getDb()->escape_string($this->toString());
        $query = "SELECT * FROM adressen WHERE adres_voluit = '$voluit' LIMIT 1;";
        $result = self::getDb()->query($query);

        if ($result && $result->num_rows >= 1) {
            $row = $result->fetch_assoc();
            return new Adres($row);
        }
        return null;
    }

    function save() {
        $gemeente = self::getDb()->escape_string($this->gemeente);
        $postcode = self::getDb()->escape_string($this->postcode);
        $straatnaam = self::getDb()->escape_string($this->straatnaam);
        $huisnummer = self::getDb()->escape_string($this->huisnummer);
        $voluit = self::getDb()->escape_string($this->toString());

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

        if (!isset($this->telefoon)) {
            $telefoon = 'NULL';
        } else {
            $telefoon = "'".self::getDb()->escape_string($this->telefoon)."'";
        }

        if (!isset($this->longitude)) {
            $longitude = 'NULL';
        } else {
            $longitude = "'".self::getDb()->escape_string($this->longitude)."'";
        }

        if (!isset($this->latitude)) {
            $latitude = 'NULL';
        } else {
            $latitude = "'".self::getDb()->escape_string($this->latitude)."'";
        }

        if (empty($this->id)) {
            $query = "INSERT INTO 
                adressen (`adres_gemeente`, `adres_postcode`, `adres_straatnaam`, `adres_huisnummer`, `adres_busnummer`, `adres_giscode`, `adres_telefoon`, `adres_longitude`, `adres_latitude`, `adres_voluit`)
                VALUES ('$gemeente', '$postcode', '$straatnaam', '$huisnummer', $busnummer, $giscode, $telefoon, $longitude, $latitude, '$voluit')";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE adressen 
                SET 
                 `adres_gemeente` = '$gemeente',
                 `adres_postcode` = '$postcode',
                 `adres_straatnaam` = '$straatnaam',
                 `adres_huisnummer` = '$huisnummer',
                 `adres_busnummer` = $busnummer,
                 `adres_giscode` = $giscode,
                 `adres_telefoon` = $telefoon,
                 `adres_longitude` = $longitude,
                 `adres_latitude` = $latitude,
                 `adres_voluit` = '$voluit'
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

    // Vergelijk strings en geef het aantal overeenkomstige karakters
    // zo groot mogelijk, als we toevoegingen toestaan
    // We doen wel pas opsplitsingen nadat eerst een gelijke match werd gevonden
    static function compareStrings($left, $right, $allow_split = true) {
        if (strlen($left) == 0 || strlen($right) == 0) {
            return 0;
        }

        $first_left = substr($left, 0, 1);
        $first_right = substr($right, 0, 1);

        if ($first_left == $first_right) {
            return 1 + static::compareStrings(substr($left, 1), substr($right, 1));
        }

        if ($allow_split) {
            // Als we splitsen, is $right altijd degene die afgekapt is geweest
            return max(static::compareStrings($left, substr($right, 1), false), static::compareStrings($right, substr($left, 1), false), static::compareStrings(substr($left, 1), substr($right, 1), false), static::compareStrings(substr($right, 1), substr($left, 1), false));
        }

        // Geen split allowed = verder doen waar we mee bezig waren = rechts afkappen
        return static::compareStrings($left, substr($right, 1), false);
    }

    // Versimpel een huisnummer bus combinatie voor vergelijkingen
    // 1 A 3 R => 1A 3R
    // A B 2 C => AB2C
    // AB 3 D => AB3D
    // 14/11 => 14 11
    // 14 bus 11 => 14 11
    // 14B => 14B
    // 1B3 45 => 1B3 45
    static function cleanHuisnummer($huisnummer) {
        // Stap 1: verwijder alle speciale tekens
        $cleanded = preg_replace('/[^0-9A-Z]+/', ' ', strtoupper($huisnummer));
        $parts = explode(' ', $cleanded);
        $str = '';

        $prev_ends_digit = false;

        foreach ($parts as $part) {
            $part = str_replace('BUS', '', $part);

            // Verwijder beginnende B's => sommige mensen gebruiken dit als BUS
            if (substr($part, 0, 1) == "B") {
                $part = substr($part, 1);
            }

            // Verwijder beginnende nullen
            while (substr($part, 0, 1) == "0") {
                $part = substr($part, 1);
            }

            $starts_digit = ctype_digit(substr($part, 0, 1));
            $ends_digit = ctype_digit(substr($part, -1));

            if ($str == '' || $prev_ends_digit !== $starts_digit || (!$prev_ends_digit && !$starts_digit)) {
                // Als A 3 of 3 A
                // of A A
                // Aan elkaar schrijven
            } else {
                $str .= ' ';
            }

            $str .= $part;
            $prev_ends_digit = $prev_ends_digit;
        }

        return $str;

    }

    static function getGisCode($straat, $postcode) {
        // No longer in use
        return null;

        // https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest/gis/code?postcode=9230&term=Markt

        $response = Curl::request(Method::GET, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest/gis/code?postcode='.$postcode.'&term='.urlencode($straat).'&Limit=1000');
        if (!isset($response)) {
            return null;
        }

        $currently = null;
        $currentMax = 0;

        foreach($response as $data) {
            $compare = strtolower($data['straat']);

            if ($straat == strtolower($compare)) {
                // Zekerheid juist
                return $data['code'];
            }
            $equality = static::compareStrings($straat, $compare);

            if ($equality > $currentMax) {
                $currently = $data['code'];
                $currentMax = $equality;
            }
        }

        return $currently;
    }

    static function getHoofdgemeente($postcode) {
        $postcodeEscaped = self::getDb()->escape_string($postcode);
        $query = "select * from gemeenten where postcode = '$postcodeEscaped'";
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0){
                $row = $result->fetch_assoc();
                return $row['hoofdgemeente'];
            }
        }
        return null;
    }

    static function fixStreetname($needle, $postcode) {
        $needle = strtolower($needle);
        $gemeente = static::getHoofdgemeente($postcode);
        if (!isset($gemeente)) {
            return null;
        }

        $straatnamen = CacheHelper::get('straatnamen_'.clean_special_chars($gemeente));

        if (!isset($straatnamen) || !is_array($straatnamen)) {
            // Todo: straatnamen per gemeente cachen, pas clearen als er geen antwoord gevonden wordt + recente cache is oud
            $response = Curl::request(Method::GET, 'https://api.basisregisters.vlaanderen.be/v1/straatnamen?Gemeentenaam='.urlencode($gemeente).'&Limit=1000');
            if (!isset($response)) {
                return null;
            }

            $straatnamen = $response['straatnamen'];

            while (isset($response['volgende'])) {
                $response = Curl::request(Method::GET, $response['volgende']);
                $straatnamen = array_merge($response['straatnamen'], $straatnamen);
            }

            if (count($straatnamen) == 0) {
                echo "empty response\n";
                return null;
            }

            // 1 week lang data bijhouden
            CacheHelper::set('straatnamen_'.clean_special_chars($gemeente), $straatnamen, 60*60*24*7);
        }

        $currently = null;
        $currentMax = 0;

        foreach($straatnamen as $straatnaam) {
            $compare = strtolower($straatnaam['straatnaam']['geografischeNaam']['spelling']);

            if ($compare == strtolower($needle)) {
                return $straatnaam['straatnaam']['geografischeNaam']['spelling'];
            }

            $equality = static::compareStrings($needle, $compare);

            if ($equality > $currentMax) {
                $currently = $straatnaam['straatnaam']['geografischeNaam']['spelling'];
                $currentMax = $equality;
            }
            else if ($equality == $currentMax) {
                // Welke lengte het dichts aanleunt bij het originele
                $currentDistance = abs(strlen($currently) - strlen($needle));
                $newDistance = abs(strlen($compare) - strlen($needle));
                
                if ($newDistance < $currentDistance) {
                    $currently = $straatnaam['straatnaam']['geografischeNaam']['spelling'];
                }
            }
        }

        // Minstens 3 overeenkomstige tekens 
        // + Minstens een vierde van gekozen straatnaam juist
        if (isset($currently)) {
            if ($currentMax < 3 || $currentMax < strlen($currently)/4) {
                return null;
            }
        }
        

        return $currently;
    }

    static function validateGemeente(&$inGemeente, &$inPostcode, &$errors) {
        $inGemeente = trim($inGemeente);
        $inPostcode = trim($inPostcode);
        
        if (empty($inGemeente)) {
            $errors[] = 'Vul een gemeente in.';
            return false;
        }
        if (empty($inPostcode)) {
            $errors[] = 'Vul een postcode in.';
            return false;;
        }
        $inGemeenteEscaped = self::getDb()->escape_string($inGemeente);
        $inPostcodeEscaped = self::getDb()->escape_string($inPostcode);
        $query = "select * from gemeenten where postcode = '$inPostcodeEscaped' or gemeente LIKE '%$inGemeente%' order by (postcode = '$inPostcodeEscaped' and gemeente = '$inGemeenteEscaped') desc, gemeente = '$inGemeenteEscaped' desc";
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0){
                $row = $result->fetch_assoc();
                if ($row['postcode'] != $inPostcode) {
                    $errors[] = 'Opgegeven postcode voor '.$inGemeente.' niet gevonden, bedoelt u '.$row['postcode'].' ('.$row['provincie'].')?';
                    return false;
                }
                elseif (strtolower($row['gemeente']) != strtolower($inGemeente)) {
                    $errors[] = 'Opgegeven gemeente niet gevonden, bedoelt u '.$row['gemeente'].' ('.$row['provincie'].')?';;
                    return false;
                } else {
                    $inGemeente = $row['gemeente'];
                    $inPostcode = $row['postcode'];
                    return true;
                }
            }
        }
        $errors[] = 'Geen gemeente gevonden met opgegeven naam en postcode.';
        return false;
    }

    // Corrigeert een adres naar de laatste data van Vlaanderen.
    // Returnt false indien mislukt en het adres ongeldig is geworden
    // De errors worden dan ingevuld met juiste errors
    function correct(&$errors = []) {
        // Todo: valideer telefoonnummer

        if (!empty($this->telefoon)) {
            Validator::validateNetPhone($this->telefoon, $this->telefoon, $errors);
        } else {
            $this->telefoon = null;
        }

        // Valideer gemeente
        if (!$this->validateGemeente($this->gemeente, $this->postcode, $errors)) {
            return false;
        }

        // Stap 1: straatnaam corrigeren
        $straatnaam = Adres::fixStreetname($this->straatnaam, $this->postcode);

        if (!isset($straatnaam)) {
            // Straat bestaat niet meer
            $errors[] = 'De opgegeven straat "'.$this->straatnaam.'" bestaat niet in '.$this->gemeente.'.';
            return false;
        }


        $needle = static::cleanHuisnummer($this->huisnummer.(isset($this->busnummer) ? ' '.$this->busnummer : ''));
        $adressen = CacheHelper::get('adressen_'.clean_special_chars($this->postcode).'_'.clean_special_chars($this->straatnaam));

        if (!isset($adressen) || !is_array($adressen)) {
            $response = Curl::request(Method::GET, 'https://api.basisregisters.vlaanderen.be/v1/adressen?Postcode='.urlencode($this->postcode).'&Straatnaam='.urlencode($this->straatnaam).'&limit=1000');
            if (!isset($response) || !isset($response['adressen'])) {
                return false;
            }

            $adressen = $response['adressen'];

            while (isset($response['volgende'])) {
                $response = Curl::request(Method::GET, $response['volgende']);
                $adressen = array_merge($response['adressen'], $adressen);
                
            }

            // 1 dag cachen
            //CacheHelper::set('adressen_'.clean_special_chars($this->postcode).'_'.clean_special_chars($this->straatnaam), $adressen, 60*60*24);
        }

        if (count($adressen) == 0) {
            $errors[] = "We hebben geen bestaande huisnummers gevonden in straat '$this->straatnaam'. Kijk even na of je geen fout maakte.";
            return false;
        }

        foreach($adressen as $mogelijkheid) {
            $huisnummer = $mogelijkheid['huisnummer'];
            
            if (isset($mogelijkheid['busnummer']) && !empty($mogelijkheid['busnummer'])) {
                $huisnummer .= ' '. $mogelijkheid['busnummer'];
            }

            $cleaned = static::cleanHuisnummer($huisnummer);

            if ($cleaned === $needle) {
                $url = $mogelijkheid['detail'];
                $detail = Curl::request(Method::GET, $url);

                if (!isset($detail)) {
                    $errors[] = "Er ging iets mis.";
                    return false;
                }

                $this->huisnummer = $detail['huisnummer'];
                $this->busnummer = isset($detail['busnummer']) ? $detail['busnummer'] : null;
                $this->giscode = static::getGisCode($this->straatnaam, $this->postcode);

                /*$coords = static::lambert72ToSpherical(floatval($detail['adresPositie']['point']['coordinates'][0]), floatval($detail['adresPositie']['point']['coordinates'][1]));

                $this->latitude = $coords->latitude;
                $this->longitude = $coords->longitude;*/

                // Correct opnieuw gevalideerd
                return count($errors) == 0;
            }
        }
        
        $errors[] = "We konden het opgegeven huisnumer niet terugvinden in de straat '$this->straatnaam'. Deze staat niet geregisteerd in de officiële databank van Vlaanderen (taak van de gemeente, CRAB-decreet). Kijk even na of je geen typfout maakte en contacteer eerst jouw gemeente als je er 100% zeker van bent dat je adres correct is.";
        return false;
    }

    // Vertaal een adres + gemeente naar een adres object adhv database vlaanderen
    // Als het mislukt wordt een deel van de gegevens gecorrigeerd
    static function find(&$adres, &$gemeente, &$postcode, &$telefoon, &$errors = []) {
        $eersteGetal = strposa($adres, ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
        if ($eersteGetal === false) {
            $eersteGetal = strlen($adres);
        }

        $straatnaam = trim(str_replace(',', '', substr($adres, 0, $eersteGetal - 1)));
        $huisnummer = trim(substr($adres, $eersteGetal));

        // Tijdelijk adres maken met mogelijks ongeldige gevens, en die later valideren
        $model = new Adres();
        $model->straatnaam = $straatnaam;
        $model->huisnummer = $huisnummer;
        $model->gemeente = $gemeente;
        $model->postcode = $postcode;
        $model->telefoon = $telefoon;


        // Stap 1: kijken of het al in onze database zit => Adres is ooit gevalideerd geweest
        // Het kan natuurlijk zijn dat het adres dat erin zit al even niet meer opnieuw gecheckt is en bv niet meer bestaat.
        // Om dat op te lossen => jaarlijks opnieuw checken
        $duplicate = $model->findDuplicate();
        if (isset($duplicate)) {
            // Todo: leeftijd checken en opnieuw valideren indien nodig

            if ($duplicate->telefoon != $telefoon) {
                $duplicate->telefoon = $model->telefoon;

                if (!$duplicate->correct($errors)) {
                    // Ongeldige gegevens
                    return null;
                }

                $duplicate->save();
            }

            return $duplicate;
        }

        // Stap 2: het zit niet in onze database
        $valid = $model->correct($errors);
        
        // Toch al een voorlopig deel gecorrigeerde gegevens doorgeven, ook bij invalid adressen
        $adres = $model->getAdres();
        $gemeente = $model->gemeente;
        $postcode = $model->postcode;
        $telefoon = $model->telefoon;

        if (!$valid) {
            return null;
        }

        // Oké, nu nog eens checken of dit adres niet al bestaat, nu het gecorrigeerd is
        // In dat geval kopiëren we het id over en overschrijven we alle data

        $duplicate = $model->findDuplicate();
        if (isset($duplicate)) {
            $model->id = $duplicate->id;
        }

        $model->save();
        return $model;
        
    }
}