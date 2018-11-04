<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leden\Steekkaart;
use Pirate\Model\Leden\Ouder;

class Lid extends Model {
    public $id;
    public $lidnummer;
    public $gezin; // Gezin object
    public $voornaam;
    public $achternaam;
    public $geslacht;
    public $geboortedatum;
    public $gsm;

    public $inschrijving; // Inschrijving object
    public $steekkaart; // Steekkaart object


    public $ouders = array(); // wordt enkel door speciale toepassingen gebruikt, niet automatisch opgevuld
    
    private static $IGNORE_LIMITS = null;

    function __construct($row = array(), $inschrijving_object = null) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];

        if (!empty($row['gezin_id'])) {
            $this->gezin = new Gezin($row);
        } else {
            $this->gezin = null;
        }

        $this->lidnummer = $row['lidnummer'];
        $this->voornaam = $row['voornaam'];
        $this->achternaam = $row['achternaam'];
        $this->geslacht = $row['geslacht'];
        $this->geboortedatum = new \DateTime($row['geboortedatum']);
        $this->gsm = $row['gsm'];

        if (!is_null($inschrijving_object)) {
            $this->inschrijving = $inschrijving_object;
        }
        elseif (!empty($row['inschrijving_id'])) {
            $this->inschrijving = new Inschrijving($row, $this);
        } else {
            $this->inschrijving = null;
        }

         if (!empty($row['steekkaart_id'])) {
            $this->steekkaart = new Steekkaart($row, $this);
        } else {
            $this->steekkaart = null;
        }
    }

    static function areLimitsIgnored() {
        if (!isset(self::$IGNORE_LIMITS)) {
            if (isset($_COOKIE['ignore_limits_inschrijven'])) {
                self::$IGNORE_LIMITS = intval($_COOKIE['ignore_limits_inschrijven']) === 1;
            } else {
                self::$IGNORE_LIMITS = false;
            }
        }

        return self::$IGNORE_LIMITS;
    }

    static function setLimitsIgnored($bool) {
        self::$IGNORE_LIMITS = $bool;

        if (!$bool) {
            setcookie('ignore_limits_inschrijven', "0", time()-604800,'/');
        } else {
            setcookie('ignore_limits_inschrijven', "1", time()+51840000,'/', '', true, true); 
        }
    }

    function getAdressen() {
        $map = array();
        foreach ($this->ouders as $ouder) {
            $adres = $ouder->getAdres();
            $map[$adres] = true; 
        }

        return array_keys($map);
    }

    function getTelefoonnummers() {
        $map = array();
        foreach ($this->ouders as $ouder) {
            if (!empty($ouder->adres->telefoon)) {
                $telefoon = $ouder->adres->telefoon;
                $map[$telefoon] = true; 
            }
        }

        return array_keys($map);
    }

    function getGeslacht() {
        if ($this->geslacht == 'M') {
            return 'Jongen';
        }
        if ($this->geslacht == 'V') {
            return 'Meisje';
        }
        return 'Onbekend geslacht';
    }

    static function getLid($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                left join inschrijvingen i on i.lid = l.id
                left join inschrijvingen i2 on i2.lid = l.id and i2.scoutsjaar > i.scoutsjaar
            where l.id = "'.$id.'" and i2.inschrijving_id is null';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Lid($row);
            }
        }
        return null;
    }

    static function getLedenForOuder($ouder) {
        $ouder = self::getDb()->escape_string($ouder);

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from ouders o
                join leden l on l.gezin = o.gezin
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                left join inschrijvingen i on i.lid = l.id
                left join inschrijvingen i2 on i2.lid = l.id and i2.scoutsjaar > i.scoutsjaar
            where o.id = "'.$ouder.'" and i2.inschrijving_id is null
            order by year(l.geboortedatum) desc, l.voornaam';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $leden[] = new Lid($row);
                }
            }
        }
        return $leden;
    }


    static function search($text) {
        // todo: Als text = getal -> omzetten in telefoonnummer
        $phone = '';
        $leden = array();
        $duplicate_fields = array('id', 'gsm', 'voornaam', 'achternaam');

        $errors = array();
        if (Validator::validateBothPhone($text, $phone, $errors, true)) {
            $text = self::getDb()->escape_string($phone);
            $query = '
                SELECT l.id as id_lid, l.gsm as gsm_lid, l.voornaam as voornaam_lid, l.achternaam as achternaam_lid, l.*, i.*, s.*, g.*, o.*, a.* from ouders o
                    join leden l on l.gezin = o.gezin
                    left join adressen a on a.adres_id = o.adres
                    left join steekkaarten s on s.lid = l.id
                    left join gezinnen g on g.gezin_id = l.gezin
                    left join inschrijvingen i on i.lid = l.id
                    left join inschrijvingen i2 on i2.lid = l.id and i2.scoutsjaar > i.scoutsjaar
                where 
                    i2.inschrijving_id is null
                    AND i.inschrijving_id is not null
                    AND
                    (
                        l.gsm LIKE "'.$text.'%" OR 
                        o.gsm LIKE "'.$text.'%" OR 
                        a.telefoon LIKE "'.$text.'%" 
                    )';

        } else {
            // todo: remove +,*,-

            // Add + before every word longer then 3 characters, add * at the end
            $newText = "";
            $currentWord = "";

            for ($i=0; $i < strlen($text); $i++) { 
                $char = substr($text, $i, 1);
                if ($char == " ") {
                    if (strlen($currentWord) > 3) {
                        $newText .=" +".$currentWord;
                    } else {
                        $newText .=" ".$currentWord;
                    }
                    $currentWord = "";
                } else {
                    $currentWord .= $char;
                }
            }

            if (strlen($currentWord) > 3) {
                $newText .=" +".$currentWord."*";
            } else {
                if (strlen($currentWord) > 0) {
                    $newText .=" ".$currentWord."*";
                }
            }

            $text = self::getDb()->escape_string($newText);

            $query = '
                SELECT l.id as id_lid, l.gsm as gsm_lid, l.voornaam as voornaam_lid, l.achternaam as achternaam_lid, l.*, i.*, s.*, g.*, o.*, a.* from ouders o
                    join leden l on l.gezin = o.gezin
                    left join adressen a on a.adres_id = o.adres
                    left join steekkaarten s on s.lid = l.id
                    left join gezinnen g on g.gezin_id = l.gezin
                    left join inschrijvingen i on i.lid = l.id
                    left join inschrijvingen i2 on i2.lid = l.id and i2.scoutsjaar > i.scoutsjaar
                where 
                    i2.inschrijving_id is null
                    AND i.inschrijving_id is not null
                    AND
                    (
                        MATCH(l.voornaam,l.achternaam,l.gsm) 
                        AGAINST("'.$text.'" IN BOOLEAN MODE)
                        OR
                        MATCH(o.voornaam,o.achternaam,o.gsm) 
                        AGAINST("'.$text.'" IN BOOLEAN MODE)
                    )';//
        }

        $leden_dict = array();

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $ouder = new Ouder($row);

                    // Fix duplicate fields
                    foreach ($duplicate_fields as $key => $value) {
                        $row[$value] = $row[$value."_lid"];
                    }
                    $lid = new Lid($row);
                    if (isset($leden_dict[$lid->id])) {
                        $lid = $leden_dict[$lid->id];
                    } else {
                        $leden[] = $lid;
                        $leden_dict[$lid->id] = $lid;
                    }
                    $lid->ouders[] = $ouder;
                }
            }
        }
        
        return $leden;
    }

    static function ledenToFieldArray($original) {
        $arr = array();
        foreach ($original as $key => $value) {
            $arr[] = $value->getPropertiesDetails();
        }
        return $arr;
    }

    function getPropertiesDetails() {
        return array(
            'id' => $this->id,
            'voornaam' => $this->voornaam,
            'achternaam' => $this->achternaam,
            'gsm' => $this->gsm,
            'tak' => $this->inschrijving->tak,
            'geboortedatum' => $this->geboortedatum->format("j-n-Y"),
            'ouders' => Ouder::oudersToFieldArray($this->ouders)
        );
    }

    static function getLedenForTak($tak, $jaar = null) {
        $tak = self::getDb()->escape_string($tak);

        if (isset($jaar)) {
            $scoutsjaar = self::getDb()->escape_string($jaar);
        } else {
            $scoutsjaar = self::getDb()->escape_string(Inschrijving::getScoutsjaar());
        }

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = "'.$scoutsjaar.'"
            where i.tak = "'.$tak.'"
            order by year(l.geboortedatum) desc, l.voornaam';


        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $leden[] = new Lid($row);
                }
            }
        }
        
        return $leden;
    }

    // Geef alle ingeschreven leden terug
    // Met ingevulde ouder objecten
    // Enkel gebruiken als je de ouder objecten nodig hebt
    static function getLedenFull() {
        $leden = Ouder::getOuders(null, null, true);
        $ouders = Ouder::getOuders();

        $gezinnen = [];
        foreach ($ouders as $ouder) {
            if (isset($gezinnen[$ouder->gezin->id])) {
                $ouder->gezin = $gezinnen[$ouder->gezin->id];
            } else {
                $gezinnen[$ouder->gezin->id] = $ouder->gezin;
            }
            $ouder->gezin->ouders[] = $ouder;
        }

        foreach ($leden as $lid) {
            if (isset($gezinnen[$lid->gezin->id])) {
                $lid->gezin = $gezinnen[$lid->gezin->id];
                $lid->ouders = $lid->gezin->ouders;
            } else {
                // Hmmm... Dit kan eigenlijk niet gebeuren
            }
        }

        return $leden;
    }

    // Geeft ook ouders mee
    static function getLedenForTakFull($tak) {
        $tak = self::getDb()->escape_string($tak);

        $scoutsjaar = self::getDb()->escape_string(Inschrijving::getScoutsjaar());

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = "'.$scoutsjaar.'"
            where i.tak = "'.$tak.'"
            order by l.voornaam, l.achternaam';


        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $lid = new Lid($row);
                    $lid->ouders = Ouder::getOudersForGezin($lid->gezin->id);
                    $leden[] = $lid;
                }
            }
        }
        
        return $leden;
    }

    function isIngeschreven() {
        if (empty($this->inschrijving)) {
            return false;
        }
        return $this->inschrijving->scoutsjaar == Inschrijving::getScoutsjaar();
    }

    // Bv. Jin van vorig jaar is niet meer inschrijfbaar (of bv na takherverdeling)
    function isInschrijfbaar() {
        $tak = $this->getTakVoorHuidigScoutsjaar(self::areLimitsIgnored());
        if ($tak === false) {
            return false;
        }
        return true;
    }

    function heeftSteekkaart() {
        if (empty($this->steekkaart)) {
            return false;
        }
        return $this->steekkaart->isIngevuld();
    }

    static function getTakkenVerdeling($scoutsjaar, $allow_limits = false) {
        $data = array(
                 $scoutsjaar - 7 => 'kapoenen', $scoutsjaar - 6 => 'kapoenen',
                 $scoutsjaar - 8 => 'wouters', $scoutsjaar - 9 => 'wouters', $scoutsjaar - 10 => 'wouters', 
                 $scoutsjaar - 11 => 'jonggivers', $scoutsjaar - 12 => 'jonggivers', $scoutsjaar - 13 => 'jonggivers',
                 $scoutsjaar - 14 => 'givers', $scoutsjaar - 15 => 'givers', $scoutsjaar - 16 => 'givers',
                 $scoutsjaar - 17 => 'jin', $scoutsjaar - 18 => 'jin'
             );


        if ($allow_limits) {
            $custom = self::getTakkenVerdelingLimits($scoutsjaar);
            foreach ($custom as $year => $tak) {
                if (!isset($data[$year])) {
                    $data[$year] = $tak;
                }
            }
        }

        return $data;
    }

    // when IGNORE_LIMITS
    static function getTakkenVerdelingLimits($scoutsjaar) {
        return array(
                // 1 - 2 jaar te jong voor kapoenen
                 $scoutsjaar - 5 => 'kapoenen', $scoutsjaar - 4 => 'kapoenen',

                 // 1 jaar te oud voor de givers (als er geen jin is)
                 $scoutsjaar - 17 => 'givers',

                 // 1 - 2 jaar te oud voor de jin (tenzij er een verlengd jin jaar is)
                 $scoutsjaar - 18 => 'jin', $scoutsjaar - 19 => 'jin'
             );
    }

    static function getTak($geboortejaar, $allow_limits = false) {
        $verdeling = self::getTakkenVerdeling(Inschrijving::getScoutsjaar());
        if (isset($verdeling[$geboortejaar])) {
            return $verdeling[$geboortejaar];
        }

        // Enkel als er geen andere tak mogelijk is, en allow limits expliciet aan staat
        if ($allow_limits) {
            $verdeling = self::getTakkenVerdelingLimits(Inschrijving::getScoutsjaar());
            if (isset($verdeling[$geboortejaar])) {
                return $verdeling[$geboortejaar];
            }
        }
        return false;
    }

    // Deze functie houdt rekening met de cookies van de gebruiker!
    function getTakVoorHuidigScoutsjaar() {
        $allow_limits = self::areLimitsIgnored();
        return self::getTak(intval($this->geboortedatum->format('Y')), $allow_limits);
    }

    function getTakVoorInschrijving() {
        if ($this->isIngeschreven()) {
            return $this->inschrijving->tak;
        }
        return null;
    }

    function schrijfIn() {
        if (!$this->isInschrijfbaar()) {
            return false;
        }
        return Inschrijving::schrijfIn($this);
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $errors = array();

        if (Validator::isValidFirstname($data['voornaam'])) {
            $this->voornaam = ucwords(mb_strtolower(trim($data['voornaam'])));
            $data['voornaam'] = $this->voornaam;
        } else {
            $errors[] = 'Ongeldige voornaam';
        }

        if (Validator::isValidLastname($data['achternaam'])) {
            $this->achternaam = ucwords(mb_strtolower(trim($data['achternaam'])));
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
            if ($this->isIngeschreven()) {
                $tak = $this->inschrijving->tak;
            } else {
                $tak = self::getTak(intval($data['geboortedatum_jaar']), self::areLimitsIgnored());
            }

            if ($tak === false) {
                $errors[] = 'Uw zoon is te oud  / jong voor de scouts. Kinderen zijn toegelaten vanaf 6 jaar. Kinderen die nog maar 5 jaar zijn, maar wel al in het eerste leerjaar zitten (noodzakelijk) kunnen een uitzondering aanvragen bij de leiding.';
            } else {
                $data['tak'] = $tak;

                if ($tak == 'givers' || $tak == 'jin') {
                    Validator::validatePhone($data['gsm'], $this->gsm, $errors);
                }
            }
        }

        if (count($errors) == 0 && $this->isDuplicate()) {
            $errors[] = 'Dit lid is al bekend in ons systeem. Het is de bedoeling dat je inlogt met het account van een ouder van dit lid om de inschrijving van dit lid te verlengen.';
        }

        return $errors;
    }

    function moetNagekekenWorden() {
        if ($this->isIngeschreven()) {
            if ($this->inschrijving->tak == 'givers' || $this->inschrijving->tak == 'jin' ) {
                $errors = array();
                if (!Validator::validatePhone($this->gsm, $this->gsm, $errors)) {
                    return true;
                }
            }
        }
        return false;
    }

    function getProperties() {
        return array(
            'voornaam' => $this->voornaam,
            'achternaam' => $this->achternaam,
            'geboortedatum_dag' => $this->geboortedatum->format('j'),
            'geboortedatum_maand' => $this->geboortedatum->format('n'),
            'geboortedatum_jaar' => $this->geboortedatum->format('Y'),
            'gsm' => $this->gsm,
            'geslacht' => $this->geslacht
        );
    }

    function isDuplicate() {
        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $geslacht = self::getDb()->escape_string($this->geslacht);
        $geboortedatum = self::getDb()->escape_string($this->geboortedatum->format('Y-m-d'));

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
             // Zoek andere ouders met dit e-mailadres
            $query = "SELECT *
            from leden
            where voornaam = '$voornaam' and achternaam = '$achternaam' and geslacht = '$geslacht' and geboortedatum = '$geboortedatum' and id != '$id'";
        } else {
             // Zoek andere ouders met dit e-mailadres
            $query = "SELECT *
            from leden
            where voornaam = '$voornaam' and achternaam = '$achternaam' and geslacht = '$geslacht' and geboortedatum = '$geboortedatum'";
        }

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0){
                return true;
            }
        } 
        
        return false;
    }

    function setGezin(Gezin $gezin) {
        $this->gezin = $gezin;
    }

    function save() {
        if (!isset($this->gsm)) {
            $gsm = "NULL";
        } else {
            $gsm = "'".self::getDb()->escape_string($this->gsm)."'";
        }

        if (!isset($this->lidnummer)) {
            $lidnummer = "NULL";
        } else {
            $lidnummer = "'".self::getDb()->escape_string($this->lidnummer)."'";
        }

        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $geslacht = self::getDb()->escape_string($this->geslacht);
        $geboortedatum = self::getDb()->escape_string($this->geboortedatum->format('Y-m-d'));

        if (!isset($this->id)) {
            if (!isset($this->gezin)) {
                return false;
            }
            $gezin = self::getDb()->escape_string($this->gezin->id);

            $query = "INSERT INTO 
                leden (`gezin`, `lidnummer`, `voornaam`, `achternaam`, `geslacht`, `geboortedatum`, `gsm`)
                VALUES ('$gezin', $lidnummer, '$voornaam', '$achternaam', '$geslacht', '$geboortedatum', $gsm)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE leden 
                SET 
                 `voornaam` = '$voornaam',
                 `achternaam` = '$achternaam',
                 `geslacht` = '$geslacht',
                 `geboortedatum` = '$geboortedatum',
                 `gsm` = $gsm,
                 `lidnummer` = $lidnummer
                 where id = '$id' 
            ";
        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }
        return false;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                leden WHERE id = '$id' ";

        if (self::getDb()->query($query)) {
            return true;
        }
        
        return false;
    }

    
}