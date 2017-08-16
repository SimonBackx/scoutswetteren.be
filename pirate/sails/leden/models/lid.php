<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leden\Steekkaart;

class Lid extends Model {
    public $id;
    public $gezin; // Gezin object
    public $voornaam;
    public $achternaam;
    public $geslacht;
    public $geboortedatum;
    public $gsm;

    public $inschrijving; // Inschrijving object
    public $steekkaart; // Steekkaart object


    public $ouders = array(); // wordt enkel door speciale toepassingen gebruikt, niet automatisch opgevuld
    

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

    function getAddress() {
        $map = array();
        foreach ($this->ouders as $ouder) {
            $adres = $ouder->getAddress();
            $map[$adres] = true; 
        }

        return array_keys($map);
    }

    function getTelefoon() {
        $map = array();
        foreach ($this->ouders as $ouder) {
            if (isset($ouder->telefoon)) {
                $telefoon = $ouder->telefoon;
                if (count($telefoon) > 0) {
                    $map[$telefoon] = true; 
                }
            }
        }

        return array_keys($map);
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

    static function getLeden($filter = null, $tak = null) {
        $where = '';

        if (!is_null($filter)) {
            if (isset(Ouder::$filters[$filter])) {
                $filter = Ouder::$filters[$filter];
                $where = $filter['where'];
            }
        }
        if (!is_null($tak)) {
            if (strlen($where) > 0)
                $where .= ' AND ';
            $where .= 'i.tak = "'.self::getDb()->escape_string($tak).'"';
        }

        if (strlen($where) > 0)
            $where = 'WHERE '.$where;

        $scoutsjaar = self::getDb()->escape_string(Inschrijving::getScoutsjaar());

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = "'.$scoutsjaar.'"
            '.$where.'
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

    function isIngeschreven() {
        if (empty($this->inschrijving)) {
            return false;
        }
        return $this->inschrijving->scoutsjaar == Inschrijving::getScoutsjaar();
    }

    function heeftSteekkaart() {
        if (empty($this->steekkaart)) {
            return false;
        }
        return $this->steekkaart->isIngevuld();
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
        $verdeling = self::getTakkenVerdeling(Inschrijving::getScoutsjaar());
        if (isset($verdeling[$geboortejaar])) {
            return $verdeling[$geboortejaar];
        }
        return false;
    }

    function getTakVoorHuidigScoutsjaar() {
        return self::getTak(intval($this->geboortedatum->format('Y')));
    }

    function schrijfIn() {
        return Inschrijving::schrijfIn($this);
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $errors = array();

        if (Validator::isValidFirstname($data['voornaam'])) {
            $this->voornaam = ucwords(mb_strtolower($data['voornaam']));
            $data['voornaam'] = $this->voornaam;
        } else {
            $errors[] = 'Ongeldige voornaam';
        }

        if (Validator::isValidLastname($data['achternaam'])) {
            $this->achternaam = ucwords(mb_strtolower($data['achternaam']));
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
            $tak = self::getTak(intval($data['geboortedatum_jaar']));

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
        $this->gezin = $gezin;
    }

    function save() {
        if (!isset($this->gsm)) {
            $gsm = "NULL";
        } else {
            $gsm = "'".self::getDb()->escape_string($this->gsm)."'";
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
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }
        return false;
    }


    
}