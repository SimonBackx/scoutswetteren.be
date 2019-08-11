<?php
namespace Pirate\Sails\Leden\Models;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Steekkaart;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Models\Validator;

class Lid extends Model
{
    public $id;
    public $lidnummer;
    public $gezin; // Gezin object
    public $voornaam;
    public $achternaam;
    public $geslacht; // M / V
    public $geboortedatum;
    public $gsm;

    public $inschrijving; // Inschrijving object
    public $steekkaart; // Steekkaart object

    public $ouders = array(); // wordt enkel door speciale toepassingen gebruikt, niet automatisch opgevuld

    private static $IGNORE_LIMITS = null;

    public function __construct($row = array(), $inschrijving_object = null)
    {
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
        } elseif (!empty($row['inschrijving_id'])) {
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

    public static function areLimitsIgnored()
    {
        if (!isset(self::$IGNORE_LIMITS)) {
            if (isset($_COOKIE['ignore_limits_inschrijven'])) {
                self::$IGNORE_LIMITS = intval($_COOKIE['ignore_limits_inschrijven']) === 1;
            } else {
                self::$IGNORE_LIMITS = false;
            }
        }

        return self::$IGNORE_LIMITS;
    }

    public static function setLimitsIgnored($bool)
    {
        self::$IGNORE_LIMITS = $bool;

        if (!$bool) {
            setcookie('ignore_limits_inschrijven', "0", time() - 604800, '/');
        } else {
            setcookie('ignore_limits_inschrijven', "1", time() + 51840000, '/', '', true, true);
        }
    }

    public function getAdressen()
    {
        $map = array();
        foreach ($this->ouders as $ouder) {
            $adres = $ouder->getAdres();
            $map[$adres] = true;
        }

        return array_keys($map);
    }

    public function getTelefoonnummers()
    {
        $map = array();
        foreach ($this->ouders as $ouder) {
            if (!empty($ouder->adres->telefoon)) {
                $telefoon = $ouder->adres->telefoon;
                $map[$telefoon] = true;
            }
        }

        return array_keys($map);
    }

    public function getGeslacht()
    {
        if ($this->geslacht == 'M') {
            return 'Jongen';
        }
        if ($this->geslacht == 'V') {
            return 'Meisje';
        }
        return 'Onbekend geslacht';
    }

    public static function getLid($id)
    {
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
            where l.id = "' . $id . '" and i2.inschrijving_id is null';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                return new Lid($row);
            }
        }
        return null;
    }

    public static function getLedenForOuder($ouder)
    {
        return static::getLedenForGezin($ouder->gezin->id);
    }

    public static function getLedenForGezin($gezin_id)
    {
        $gezin_id = self::getDb()->escape_string($gezin_id);

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                left join inschrijvingen i on i.lid = l.id
                left join inschrijvingen i2 on i2.lid = l.id and i2.scoutsjaar > i.scoutsjaar
            where g.gezin_id = "' . $gezin_id . '" and i2.inschrijving_id is null
            order by year(l.geboortedatum) desc, l.voornaam';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $leden[] = new Lid($row);
                }
            }
        }
        return $leden;
    }

    public static function search($text)
    {
        // todo: Als text = getal -> omzetten in telefoonnummer
        $phone = '';
        $leden = array();
        $duplicate_fields = array('id', 'gsm', 'voornaam', 'achternaam');

        $errors = array();

        $words = explode(' ', $text);
        $subtext = '';
        foreach ($words as $word) {
            if (empty($word)) {
                continue;
            }
            if (!empty($subtext)) {
                $subtext .= ' ';
            }
            $subtext .= '+' . self::getDb()->escape_string(str_replace(['+', '-', '*'], ['\\+', '', ''], $word)) . '*';
        }

        $text = $subtext;

        $query = '
        SELECT l.id as id_lid, l.gsm as gsm_lid, l.voornaam as voornaam_lid, l.achternaam as achternaam_lid, l.*, i.*, s.*, g.*, o.*, a.*, u.* from leden_search
            join leden l on l.id = leden_search.search_lid
            join ouders o on l.gezin = o.gezin
            join users u on o.user_id  = u.user_id
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
                MATCH(leden_search.search_text)
                AGAINST(\'' . $text . '\' IN BOOLEAN MODE)
            )'; //

        $leden_dict = array();

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $ouder = new Ouder($row);

                    // Fix duplicate fields
                    foreach ($duplicate_fields as $key => $value) {
                        $row[$value] = $row[$value . "_lid"];
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
        } else {
            echo self::getDb()->error;
        }

        return $leden;
    }

    public static function ledenToFieldArray($original)
    {
        $arr = array();
        foreach ($original as $key => $value) {
            $arr[] = $value->getPropertiesDetails();
        }
        return $arr;
    }

    public function getPropertiesDetails()
    {
        return array(
            'id' => $this->id,
            'voornaam' => $this->voornaam,
            'achternaam' => $this->achternaam,
            'gsm' => $this->gsm,
            'tak' => $this->inschrijving->tak,
            'geboortedatum' => $this->geboortedatum->format("j-n-Y"),
            'ouders' => Ouder::oudersToFieldArray($this->ouders),
        );
    }

    public static function getLedenForTak($tak, $jaar = null)
    {
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
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = "' . $scoutsjaar . '"
            where i.tak = "' . $tak . '" and i.datum_uitschrijving is null
            order by year(l.geboortedatum) desc, l.voornaam';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
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
    public static function getLedenFull()
    {
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
    /// Redelijk zware query momenteel
    public static function getLedenForTakFull($tak)
    {
        $tak = self::getDb()->escape_string($tak);

        $scoutsjaar = self::getDb()->escape_string(Inschrijving::getScoutsjaar());

        $leden = array();
        $query = '
            SELECT l.*, i.*, s.*, g.* from leden l
                left join steekkaarten s on s.lid = l.id
                left join gezinnen g on g.gezin_id = l.gezin
                join inschrijvingen i on i.lid = l.id and i.scoutsjaar = "' . $scoutsjaar . '"
            where i.tak = "' . $tak . '" and i.datum_uitschrijving is null
            order by l.voornaam, l.achternaam';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $lid = new Lid($row);
                    $lid->ouders = Ouder::getOudersForGezin($lid->gezin->id);
                    $leden[] = $lid;
                }
            }
        }

        return $leden;
    }

    public function isIngeschreven()
    {
        if (empty($this->inschrijving)) {
            return false;
        }
        return $this->inschrijving->scoutsjaar == Inschrijving::getScoutsjaar() && empty($this->inschrijving->datum_uitschrijving);
    }

    // Bv. Jin van vorig jaar is niet meer inschrijfbaar (of bv na takherverdeling)
    public function isInschrijfbaar()
    {
        $tak = $this->getTakVoorHuidigScoutsjaar(self::areLimitsIgnored());
        if ($tak === false) {
            return false;
        }
        return true;
    }

    public function heeftSteekkaart()
    {
        if (empty($this->steekkaart)) {
            return false;
        }
        return $this->steekkaart->isIngevuld();
    }

    // Mapping from birth year to tak name
    public static function getTakkenVerdeling($scoutsjaar, $gender, $allow_limits = false)
    {
        $takken = Environment::getSetting('scouts.takken');

        $data = [];

        foreach ($takken as $tak_key => $tak) {
            if ($tak['auto_assign'] && !isset($tak['gender']) || $tak['gender'] == $gender) {
                for ($age = $tak['age_start']; $age <= $tak['age_end']; $age++) {
                    $data[$scoutsjaar - $age] = $tak_key;
                }
            }
        }

        if ($allow_limits) {
            // Extend minimum and maximum with 2 years
            $minimum = min(array_keys($data));
            $maximum = max(array_keys($data));

            $data[$minimum - 1] = $data[$minimum];
            $data[$minimum - 2] = $data[$minimum];

            $data[$maximum - 1] = $data[$maximum];
            $data[$maximum - 2] = $data[$maximum];

        }

        return $data;
    }

    /// Return the automatic tak for the current lid. Try to stay with the current tak if possible (if it doesnt have auto assign)
    public function getTakVoorHuidigScoutsjaar()
    {
        // Check if we are in a non auto assign tak
        if (isset($this->inschrijving)) {
            $takken = Environment::getSetting('scouts.takken');
            if (isset($takken[$this->inschrijving->tak]) && !$takken[$this->inschrijving->tak]['auto_assign']) {
                return $this->inschrijving->tak;
            }
        }

        $allow_limits = self::areLimitsIgnored();
        $geboortejaar = intval($this->geboortedatum->format('Y'));
        $gender = $this->geslacht;

        $verdeling = self::getTakkenVerdeling(Inschrijving::getScoutsjaar(), $gender, $allow_limits);
        if (isset($verdeling[$geboortejaar])) {
            return $verdeling[$geboortejaar];
        }

        return false;
    }

    public function getTakVoorInschrijving()
    {
        if ($this->isIngeschreven()) {
            return $this->inschrijving->tak;
        }
        return null;
    }

    public function schrijfIn()
    {
        if (!$this->isInschrijfbaar()) {
            return false;
        }

        return Inschrijving::schrijfIn($this);
    }

    // empty array on success
    // array of errors on failure
    public function setProperties(&$data)
    {
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

        $geboortedatum = $data['geboortedatum_jaar'] . '-' . $data['geboortedatum_maand'] . '-' . $data['geboortedatum_dag'];
        $geboortedatum = \DateTime::createFromFormat('Y-n-j', $geboortedatum);
        if ($geboortedatum !== false && checkdate($data['geboortedatum_maand'], $data['geboortedatum_dag'], $data['geboortedatum_jaar'])) {
            $this->geboortedatum = clone $geboortedatum;
            $data['geboortedatum_dag'] = $geboortedatum->format('j');
            $data['geboortedatum_maand'] = $geboortedatum->format('n');
            $data['geboortedatum_jaar'] = $geboortedatum->format('Y');
        } else {
            $errors[] = 'Ongeldige geboortedatum';
        }

        if (isset($this->geboortedatum, $this->geslacht, $this->voornaam)) {
            if ($this->isIngeschreven()) {
                $tak = $this->inschrijving->tak;
            } else {
                $tak = $this->getTakVoorHuidigScoutsjaar();
            }

            if ($tak === false) {
                $errors[] = $this->voornaam . ' is te oud  / jong voor de scouts. Kinderen zijn toegelaten vanaf 6 jaar. Kinderen die nog maar 5 jaar zijn, maar wel al in het eerste leerjaar zitten (noodzakelijk) kunnen een uitzondering aanvragen bij de leiding.';
            } else {
                $data['tak'] = $tak;

                if (Environment::getSetting("scouts.takken.$tak.require_mobile", false)) {
                    Validator::validatePhone($data['gsm'], $this->gsm, $errors);
                }
            }
        }

        if (count($errors) == 0 && $this->isDuplicate()) {
            $errors[] = 'Dit lid is al bekend in ons systeem. Het is de bedoeling dat je inlogt met het account van een ouder van dit lid om de inschrijving van dit lid te verlengen.';
        }

        return $errors;
    }

    public function moetNagekekenWorden()
    {
        if ($this->isIngeschreven()) {
            if ($this->inschrijving->tak == 'givers' || $this->inschrijving->tak == 'jin') {
                $errors = array();
                if (!Validator::validatePhone($this->gsm, $this->gsm, $errors)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getProperties()
    {
        return array(
            'voornaam' => $this->voornaam,
            'achternaam' => $this->achternaam,
            'geboortedatum_dag' => $this->geboortedatum->format('j'),
            'geboortedatum_maand' => $this->geboortedatum->format('n'),
            'geboortedatum_jaar' => $this->geboortedatum->format('Y'),
            'gsm' => $this->gsm,
            'geslacht' => $this->geslacht,
        );
    }

    public function isDuplicate()
    {
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
            if ($result->num_rows > 0) {
                return true;
            }
        }

        return false;
    }

    public function setGezin(Gezin $gezin)
    {
        $this->gezin = $gezin;
    }

    public function save()
    {
        if (!isset($this->gsm)) {
            $gsm = "NULL";
        } else {
            $gsm = "'" . self::getDb()->escape_string($this->gsm) . "'";
        }

        if (!isset($this->lidnummer)) {
            $lidnummer = "NULL";
        } else {
            $lidnummer = "'" . self::getDb()->escape_string($this->lidnummer) . "'";
        }

        if (!isset($this->gezin)) {
            return false;
        }
        $gezin = self::getDb()->escape_string($this->gezin->id);

        $voornaam = self::getDb()->escape_string($this->voornaam);
        $achternaam = self::getDb()->escape_string($this->achternaam);
        $geslacht = self::getDb()->escape_string($this->geslacht);
        $geboortedatum = self::getDb()->escape_string($this->geboortedatum->format('Y-m-d'));

        if (!isset($this->id)) {

            $query = "INSERT INTO
                leden (`gezin`, `lidnummer`, `voornaam`, `achternaam`, `geslacht`, `geboortedatum`, `gsm`)
                VALUES ('$gezin', $lidnummer, '$voornaam', '$achternaam', '$geslacht', '$geboortedatum', $gsm)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE leden
                SET
                 `voornaam` = '$voornaam',
                 `gezin` = '$gezin',
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

            $this->updateSearchIndex();
            return true;
        }
        return false;
    }

    public function updateSearchIndex()
    {
        // Generate text here:
        $text = '';
        $text .= "$this->voornaam $this->achternaam\n";
        $text .= str_replace([' '], [' '], $this->gsm) . "\n"; // remove non breaking spaces

        // Withotu spaces
        $text .= str_replace([' ', ' '], ['', ''], $this->gsm) . "\n";

        // With land code replaced by a zero
        $text .= str_replace(['+32', '+31'], ['0', '0'], str_replace([' ', ' '], ['', ''], $this->gsm)) . "\n";

        // Todo: add ouders

        $id = self::getDb()->escape_string($this->id);
        $text = self::getDb()->escape_string($text);

        $query = "INSERT INTO leden_search (`search_lid`, `search_text`) VALUES ('$id', '$text')
        ON DUPLICATE KEY UPDATE search_text='$text';";

        if (self::getDb()->query($query)) {
            return true;
        }
        return false;
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                leden WHERE id = '$id' ";

        if (self::getDb()->query($query)) {
            return true;
        }

        return false;
    }

    /// Voeg alle data van een ouder lid $lid toe aan dit lid (inschrijvingen, steekkaarten)
    /// Afrekeningen worden niet aangepast, dat moet gebeuren bij het mergen van Gezinnen
    public function merge($lid)
    {
        if ($lid->id == $this->id) {
            // Prevent data loss
            return false;
        }

        $id = self::getDb()->escape_string($lid->id);
        $new_id = self::getDb()->escape_string($this->id);

        $query = "UPDATE inschrijvingen
            SET
             `lid` = '$new_id'
             where lid = '$id'
        ";

        if (!self::getDb()->query($query)) {
            return false;
        }

        // Verwijder lid + steekkaaretn
        return $lid->delete();
    }

    /// Return true when users are probably the same
    public function isProbablyEqual($lid)
    {
        if (
            trim(clean_special_chars($lid->voornaam)) == trim(clean_special_chars($this->voornaam))
            && (trim(clean_special_chars($lid->achternaam)) == trim(clean_special_chars($this->achternaam)) || $lid->geboortedatum->format('Y-m-d') == $this->geboortedatum->format('Y-m-d'))
        ) {
            return true;
        }
        return false;
    }

}
