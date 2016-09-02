<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;

class Steekkaart extends Model {
    public $id;
    public $lid; // object Lid
    public $laatst_nagekeken;
    public $nagekeken_door;
    public $nagekeken_door_titel;

    public $contactpersoon_naam;
    public $contactpersoon_gsm;
    public $contactpersoon_functie;

    public $verblijfsinstelling;
    public $deelname_onmogelijke_activiteiten;
    public $deelname_reden;
    public $deelname_sporten;
    public $deelname_sociaal;
    public $deelname_hygiene;
    public $deelname_andere;

    public $medisch_toestemming_medicatie;
    public $medisch_specifieke_medicatie;

    public $medisch_ziekten;
    public $medisch_ziekten_aanpak;

    public $medisch_dieet;
    public $medisch_klem_jaar;
    public $bloedgroep;

    public $huisarts_naam;
    public $huisarts_telefoon;

    public $toestemming_fotos;
    public $aanvullend_voeding;
    public $aanvullend_andere;

    function __construct($row = array(), $lid_object = null) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['steekkaart_id'];

        if (!empty($row['laatst_nagekeken'])) {
            $this->laatst_nagekeken = new \DateTime($row['laatst_nagekeken']);
        } else {
            $this->laatst_nagekeken = null;
        }

        if (is_null($lid_object)) {
            $this->lid = new Lid($row);
        } else {
            $this->lid = $lid_object;
        }

        $this->nagekeken_door = $row['nagekeken_door'];
        $this->nagekeken_door_titel = $row['nagekeken_door_titel'];
        $this->contactpersoon_naam = $row['contactpersoon_naam'];
        $this->contactpersoon_gsm = $row['contactpersoon_gsm'];
        $this->contactpersoon_functie = $row['contactpersoon_functie'];
        $this->verblijfsinstelling = $row['verblijfsinstelling'];

        $this->deelname_onmogelijke_activiteiten = $row['deelname_onmogelijke_activiteiten'];
        $this->deelname_reden = $row['deelname_reden'];
        $this->deelname_sporten = $row['deelname_sporten'];
        $this->deelname_sociaal = $row['deelname_sociaal'];
        $this->deelname_hygiene = $row['deelname_hygiene'];
        $this->deelname_andere = $row['deelname_andere'];

        $this->medisch_toestemming_medicatie = $row['medisch_toestemming_medicatie'];
        $this->medisch_specifieke_medicatie = $row['medisch_specifieke_medicatie'];
        $this->medisch_ziekten = $row['medisch_ziekten'];
        $this->medisch_ziekten_aanpak = $row['medisch_ziekten_aanpak'];

        $this->medisch_dieet = $row['medisch_dieet'];
        $this->medisch_klem_jaar = $row['medisch_klem_jaar'];
        $this->bloedgroep = $row['bloedgroep'];
        $this->huisarts_naam = $row['huisarts_naam'];
        $this->huisarts_telefoon = $row['huisarts_telefoon'];

        $this->toestemming_fotos = $row['toestemming_fotos'];
        $this->aanvullend_voeding = $row['aanvullend_voeding'];
        $this->aanvullend_andere = $row['aanvullend_andere'];
    }

    function getNagekekenString() {
        if (empty($this->laatst_nagekeken)) {
            return 'Nog niet ingevuld';
        }
        return datetimeToDateString($this->laatst_nagekeken);
    }

    // $data is een array met alle data die nagekeken moet worden
    // en indien goed, overgezet moet worden op het huidige object
    // $errors bevat een lijst met fouten
    // return true wanneer succesvol
     // Return true on success
    function setProperties(&$data, &$bereikbaarheid_errors, &$deelname_errors, &$medische_errors, &$aanvullende_errors, &$bevestiging_errors) {

        // Bereikbaarheid ------------------------------------------------------
        
        if (Validator::isValidName($data['contactpersoon_naam'])) {
            $this->contactpersoon_naam = ucwords($data['contactpersoon_naam']);
            $data['contactpersoon_naam'] = $this->contactpersoon_naam;
        } else {
            $bereikbaarheid_errors[] = 'Naam van contactpersoon is ongeldig';
        }

        Validator::validatePhone($data['contactpersoon_gsm'], $this->contactpersoon_gsm, $bereikbaarheid_errors);
        
        if (strlen($data['contactpersoon_functie']) > 2) {
            $this->contactpersoon_functie = ucfirst($data['contactpersoon_functie']);
            $data['contactpersoon_functie'] = $this->contactpersoon_functie;
        } else {
            $bereikbaarheid_errors[] = 'Functie van contactpersoon is ongeldig';
        }

        if (strlen($data['verblijfsinstelling']) > 0) {
            $this->verblijfsinstelling = ucfirst($data['verblijfsinstelling']);
            $data['verblijfsinstelling'] = $this->verblijfsinstelling;
        } else {
            $this->verblijfsinstelling = null;
        }

        // Deelname aan activiteiten ------------------------------------------------------
        if ($data['deelname_onmogelijke_activiteiten_radio'] != 'ja' && $data['deelname_onmogelijke_activiteiten_radio'] != 'nee') {
            $deelname_errors[] = 'Gelieve ja of nee aan te vinken';
        } elseif ($data['deelname_onmogelijke_activiteiten_radio'] == 'nee') {
            if (empty($data['deelname_onmogelijke_activiteiten'])) {
                $deelname_errors[] = 'Gelieve in te vullen aan welke activiteiten niet kan/mag worden deelgenomen.';
            } else {
                $this->deelname_onmogelijke_activiteiten = ucsentence($data['deelname_onmogelijke_activiteiten']);
                $data['deelname_onmogelijke_activiteiten'] = $this->deelname_onmogelijke_activiteiten;

                // reden is optioneel
                $this->deelname_reden = ucsentence($data['deelname_reden']);
                $data['deelname_reden'] = $this->deelname_reden;
            }
        } else {
            $this->deelname_onmogelijke_activiteiten = null;
            $this->deelname_reden = null;
        }

        $this->deelname_sporten = ucsentence($data['deelname_sporten']);
        $data['deelname_sporten'] = $this->deelname_sporten;

        $this->deelname_hygiene = ucsentence($data['deelname_hygiene']);
        $data['deelname_hygiene'] = $this->deelname_hygiene;

        $this->deelname_sociaal = ucsentence($data['deelname_sociaal']);
        $data['deelname_sociaal'] = $this->deelname_sociaal;

        $this->deelname_andere = ucsentence($data['deelname_andere']);
        $data['deelname_andere'] = $this->deelname_andere;
        
        // Medische gegevens ------------------------------------------------------

        if ($data['medisch_toestemming_medicatie'] != 'ja' && $data['medisch_toestemming_medicatie'] != 'nee') {
            $medische_errors[] = 'Gelieve toestemming voor medicatie aan te vinken met ja of nee';
        } else {
            $this->medisch_toestemming_medicatie = $data['medisch_toestemming_medicatie'];
        }
        if ($data['medisch_specifieke_medicatie'] != 'ja' && $data['medisch_specifieke_medicatie'] != 'nee') {
            $medische_errors[] = 'Gelieve specifieke medicatie aan te vinken met ja of nee';
        } else {
            $this->medisch_specifieke_medicatie = $data['medisch_specifieke_medicatie'];
        }

        if ($data['medisch_ziekten_checkbox'] != 'ja' && $data['medisch_ziekten_checkbox'] != 'nee') {
            $medische_errors[] = 'Gelieve ziekten aan te vinken met ja of nee';
        } elseif ($data['medisch_ziekten_checkbox'] == 'ja') {
            if (empty($data['medisch_ziekten'])) {
                $medische_errors[] = 'Gelieve in te vullen over welke ziekten het gaat.';
            } else {
                $this->medisch_ziekten = ucsentence($data['medisch_ziekten']);
                $data['medisch_ziekten'] = $this->medisch_ziekten;

                // aanpak is optioneel
                $this->medisch_ziekten_aanpak = ucsentence($data['medisch_ziekten_aanpak']);
                $data['medisch_ziekten_aanpak'] = $this->medisch_ziekten_aanpak;
            }
        } else {
            $this->medisch_ziekten = null;
            $this->medisch_ziekten_aanpak = null;
        }
        
        if ($data['medisch_dieet_checkbox'] != 'ja' && $data['medisch_dieet_checkbox'] != 'nee') {
            $medische_errors[] = 'Gelieve dieet aan te vinken met ja of nee';
        } elseif ($data['medisch_dieet_checkbox'] == 'ja') {
            if (empty($data['medisch_dieet'])) {
                $medische_errors[] = 'Gelieve in te vullen over welk dieet het gaat.';
            } else {
                $this->medisch_dieet = ucsentence($data['medisch_dieet']);
                $data['medisch_dieet'] = $this->medisch_dieet;
            }
        } else {
            $this->medisch_dieet = null;
        }

        if ($data['medisch_klem_checkbox'] != 'ja' && $data['medisch_klem_checkbox'] != 'nee') {
            $medische_errors[] = 'Gelieve tetanus aan te vinken met ja of nee';
        } elseif ($data['medisch_klem_checkbox'] == 'ja') {
            if (empty($data['medisch_klem_jaar'])) {
                $medische_errors[] = 'Gelieve in te vullen in welk jaar tegen klem ingeënt werd.';
            } else {
                $klem = intval($data['medisch_klem_jaar']);
                if ($klem < 1990 || $data['medisch_klem_jaar'] > intval(date('Y'))) {
                    $medische_errors[] = 'Ongeldig jaar waarin tegen klem ingeënt werd.';
                } else {
                    $this->medisch_klem_jaar = $klem;
                    $data['medisch_klem_jaar'] = $klem;
                }
            }
        } else {
            $this->medisch_klem_jaar = null;
        }

        if (Validator::isValidName($data['huisarts_naam'])) {
            $this->huisarts_naam = ucwords($data['huisarts_naam']);
            $data['huisarts_naam'] = $this->huisarts_naam;
        } else {
            $medische_errors[] = 'Naam van huisarts is ongeldig';
        }

        Validator::validateBothPhone($data['huisarts_telefoon'], $this->huisarts_telefoon, $medische_errors);

        if (empty($data['bloedgroep'])) {
            $medische_errors[] = 'Gelieve een bloedgroep te selecteren.';
        } else {
            if (in_array($data['bloedgroep'], array('onbekend','O-','O+','A+','A-','B+','B-','AB+','AB-'))) {
                $this->bloedgroep = $data['bloedgroep'];
            } else {
                $medische_errors[] = 'Ongeldige bloedgroep. Contacteer webmaster bij problemen.';
            }
        }

        // Aanvullende gegevens ------------------------------------------------------

        if ($data['toestemming_fotos'] != 'ja' && $data['toestemming_fotos'] != 'nee') {
            $aanvullende_errors[] = 'Gelieve toestemming voor foto\'s aan te vinken met ja of nee';
        } else {
            $this->toestemming_fotos = $data['toestemming_fotos'];
        }

        $this->aanvullend_voeding = ucsentence($data['aanvullend_voeding']);
        $data['aanvullend_voeding'] = $this->aanvullend_voeding;

        $this->aanvullend_andere = ucsentence($data['aanvullend_andere']);
        $data['aanvullend_andere'] = $this->aanvullend_andere;

        if (Validator::isValidName($data['nagekeken_door'])) {
            $this->nagekeken_door = ucwords($data['nagekeken_door']);
            $data['nagekeken_door'] = $this->nagekeken_door;
        } else {
            $bevestiging_errors[] = 'Opgegeven naam ongeldig';
        }

        if (!in_array($data['nagekeken_door_titel'], array('ouder','voogd'))) {
            $bevestiging_errors[] = 'Geen titel geselecteerd';
        } else {
            $this->nagekeken_door_titel = $data['nagekeken_door_titel'];
        }

        return (count($bevestiging_errors)+count($aanvullende_errors)+count($medische_errors)+count($deelname_errors)+count($bereikbaarheid_errors) == 0);
    }

    function setLid(Lid $lid) {
        $this->lid = $lid;
    }

    function isIngevuld() {
        return !empty($this->nagekeken_door);
    }

    // Inschrijvingen vanaf juni verbieden
    function moetNagekekenWorden() {
        if (!$this->isIngevuld()) {
            return false;
        }

        if (empty($this->laatst_nagekeken)) {
            return true;
        }
        
        $jaar = intval($this->laatst_nagekeken->format('Y'));
        $maand = intval($this->laatst_nagekeken->format('n'));
        if ($maand < 9) {
            $jaar--;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->laatst_nagekeken);

        if ($jaar != Lid::getScoutsjaar() && $interval->days > 30) {
            return true;
        }

        return false;
    }

   /* public $id;
    public $lid;
    public $laatst_nagekeken;
    public $nagekeken_door;
    public $nagekeken_door_titel;

    public $contactpersoon_naam;
    public $contactpersoon_gsm;
    public $contactpersoon_functie;

    public $verblijfsinstelling;
    public $deelname_onmogelijke_activiteiten;
    public $deelname_reden;
    public $deelname_sporten;
    public $deelname_sociaal;
    public $deelname_hygiene;
    public $deelname_andere;

    public $medisch_toestemming_medicatie;
    public $medisch_specifieke_medicatie;

    public $medisch_ziekten;
    public $medisch_ziekten_aanpak;

    public $medisch_dieet;
    public $medisch_klem_jaar;
    public $bloedgroep;

    public $huisarts_naam;
    public $huisarts_telefoon;

    public $toestemming_fotos;
    public $aanvullend_voeding;
    public $aanvullend_andere;*/

    function save() {
        if (empty($this->lid)) {
            return false;
        }
        $lid = self::getDb()->escape_string($this->lid->id);

        // 2 opties: mét alle data, of helemaal zonder enige data
        if (empty($this->nagekeken_door)) {
            // Helemaal zonder enige data, enkel voor overslaan functie
            if (empty($this->id)) {
                $query = "INSERT INTO 
                steekkaarten (`lid`)
                VALUES ('$lid')";

                if (self::getDb()->query($query)) {
                    $this->id = self::getDb()->insert_id;
                    return true;
                }
                return false;

            } else {
                // Er moet niets gebeuren
               return true;
            }
        }

        // Alles wat null kan worden
        // verblijfsinstelling
        // deelname_onmogelijke_activiteiten
        // deelname_reden
        // medisch_ziekten
        // medisch_ziekten_aanpak (nooit null, maar null maken indien '')
        // medisch_dieet
        // medisch_klem_jaar

        if (empty($this->verblijfsinstelling)) {
            $verblijfsinstelling = "NULL";
        } else {
            $verblijfsinstelling = "'".self::getDb()->escape_string($this->verblijfsinstelling)."'";
        }
        if (empty($this->deelname_onmogelijke_activiteiten)) {
            $deelname_onmogelijke_activiteiten = "NULL";
        } else {
            $deelname_onmogelijke_activiteiten = "'".self::getDb()->escape_string($this->deelname_onmogelijke_activiteiten)."'";
        }
        if (empty($this->deelname_reden)) {
            $deelname_reden = "NULL";
        } else {
            $deelname_reden = "'".self::getDb()->escape_string($this->deelname_reden)."'";
        }
        if (empty($this->medisch_ziekten)) {
            $medisch_ziekten = "NULL";
        } else {
            $medisch_ziekten = "'".self::getDb()->escape_string($this->medisch_ziekten)."'";
        }
        if (empty($this->medisch_ziekten_aanpak)) {
            $medisch_ziekten_aanpak = "NULL";
        } else {
            $medisch_ziekten_aanpak = "'".self::getDb()->escape_string($this->medisch_ziekten_aanpak)."'";
        }
        if (empty($this->medisch_dieet)) {
            $medisch_dieet = "NULL";
        } else {
            $medisch_dieet = "'".self::getDb()->escape_string($this->medisch_dieet)."'";
        }
        if (empty($this->medisch_klem_jaar)) {
            $medisch_klem_jaar = "NULL";
        } else {
            $medisch_klem_jaar = "'".self::getDb()->escape_string($this->medisch_klem_jaar)."'";
        }

        $laatst_nagekeken = (new \DateTime())->format('Y-m-d H:i');
        $nagekeken_door = self::getDb()->escape_string($this->nagekeken_door);
        $nagekeken_door_titel = self::getDb()->escape_string($this->nagekeken_door_titel);
        $contactpersoon_naam = self::getDb()->escape_string($this->contactpersoon_naam);
        $contactpersoon_gsm = self::getDb()->escape_string($this->contactpersoon_gsm);
        $contactpersoon_functie = self::getDb()->escape_string($this->contactpersoon_functie);
        $deelname_sporten = self::getDb()->escape_string($this->deelname_sporten);
        $deelname_sociaal = self::getDb()->escape_string($this->deelname_sociaal);
        $deelname_hygiene = self::getDb()->escape_string($this->deelname_hygiene);
        $deelname_andere = self::getDb()->escape_string($this->deelname_andere);
        $medisch_toestemming_medicatie = self::getDb()->escape_string($this->medisch_toestemming_medicatie);
        $medisch_specifieke_medicatie = self::getDb()->escape_string($this->medisch_specifieke_medicatie);
        $bloedgroep = self::getDb()->escape_string($this->bloedgroep);
        $huisarts_naam = self::getDb()->escape_string($this->huisarts_naam);
        $huisarts_telefoon = self::getDb()->escape_string($this->huisarts_telefoon);

        $toestemming_fotos = self::getDb()->escape_string($this->toestemming_fotos);
        $aanvullend_voeding = self::getDb()->escape_string($this->aanvullend_voeding);
        $aanvullend_andere = self::getDb()->escape_string($this->aanvullend_andere);

        if (empty($this->id)) {
            $query = "INSERT INTO 
                steekkaarten (`lid`, `laatst_nagekeken`, `nagekeken_door` , `nagekeken_door_titel`, `contactpersoon_naam`, `contactpersoon_gsm`, `contactpersoon_functie`, `verblijfsinstelling`, `deelname_onmogelijke_activiteiten`, `deelname_reden`, `deelname_sporten`, `deelname_sociaal`, `deelname_hygiene`, `deelname_andere`, `medisch_toestemming_medicatie`, `medisch_specifieke_medicatie`, `medisch_ziekten`, `medisch_ziekten_aanpak`, `medisch_dieet`, `medisch_klem_jaar`, `bloedgroep`, `huisarts_naam`, `huisarts_telefoon`, `toestemming_fotos`, `aanvullend_voeding`, `aanvullend_andere`)
                VALUES ('$lid', '$laatst_nagekeken', '$nagekeken_door' , '$nagekeken_door_titel', '$contactpersoon_naam', '$contactpersoon_gsm', '$contactpersoon_functie', $verblijfsinstelling, $deelname_onmogelijke_activiteiten, $deelname_reden, '$deelname_sporten', '$deelname_sociaal', '$deelname_hygiene', '$deelname_andere', '$medisch_toestemming_medicatie', '$medisch_specifieke_medicatie', $medisch_ziekten, $medisch_ziekten_aanpak, $medisch_dieet, $medisch_klem_jaar, '$bloedgroep', '$huisarts_naam', '$huisarts_telefoon', '$toestemming_fotos', '$aanvullend_voeding', '$aanvullend_andere')";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE steekkaarten 
                SET 
                 `lid` = '$lid',
                 `laatst_nagekeken` = '$laatst_nagekeken',
                 `nagekeken_door` = '$nagekeken_door', 
                 `nagekeken_door_titel` = '$nagekeken_door_titel', 
                 `contactpersoon_naam` = '$contactpersoon_naam',
                 `contactpersoon_gsm` = '$contactpersoon_gsm',
                 `contactpersoon_functie` = '$contactpersoon_functie',
                 `verblijfsinstelling` = $verblijfsinstelling,
                 `deelname_onmogelijke_activiteiten` = $deelname_onmogelijke_activiteiten,
                 `deelname_reden` = $deelname_reden,
                 `deelname_sporten` = '$deelname_sporten',
                 `deelname_sociaal` = '$deelname_sociaal',
                 `deelname_hygiene` = '$deelname_hygiene',
                 `deelname_andere` = '$deelname_andere',
                 `medisch_toestemming_medicatie` = '$medisch_toestemming_medicatie',
                 `medisch_specifieke_medicatie` = '$medisch_specifieke_medicatie',
                 `medisch_ziekten` = $medisch_ziekten,
                 `medisch_ziekten_aanpak` = $medisch_ziekten_aanpak,
                 `medisch_dieet` = $medisch_dieet,
                 `medisch_klem_jaar` = $medisch_klem_jaar,
                 `bloedgroep` = '$bloedgroep',
                 `huisarts_naam` = '$huisarts_naam',
                 `huisarts_telefoon` = '$huisarts_telefoon',
                 `toestemming_fotos` = '$toestemming_fotos',
                 `aanvullend_voeding` = '$aanvullend_voeding',
                 `aanvullend_andere` = '$aanvullend_andere'
                where steekkaart_id = '$id' 
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