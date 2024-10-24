<?php
namespace Pirate\Sails\Verhuur\Models;

use Pirate\Sails\AmazonSes\Models\Mail;
use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Adres;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Model;

class Reservatie extends Model
{
    public $id;
    public $contract_nummer;
    public $startdatum;
    public $einddatum;

    public $door_leiding; // voorlopig geowoon id, later Leidings object of null

    public $personen;
    public $personen_tenten;

    public $groep;
    public $contact_naam;
    public $contact_email;
    public $contact_gsm;

    public $contact_adres;
    public $contact_gemeente;
    public $contact_postcode;
    public $contact_land;

    public $info;

    public $opmerkingen;

    public $waarborg;
    public $huur;

    public $ligt_vast = false; // true / false
    public $waarborg_betaald = false; // true / false
    public $huur_betaald = false; // true / false
    public $leidingsweekend = false; // true / false
    public $contract_oke = false; // true / false

    public $aanvraag_datum;

    /*public static $max_gebouw = 35;
    public static $max_tenten = 20;

    public static $prijzen = array(2016 => 95, 2017 => 98, 2018 => 100, 2019 => 102, 2020 => 105, 2021 => 110);
    public static $waarborg_weekend = 400;
    public static $waarborg_kamp = 750;
    public static $prijs_tent_nacht = 20;
    public static $prijs_tent_persoon = 2;*/

    private $no_mail = false;

    public static function getPrijzenPerJaar()
    {
        return Environment::getSetting('verhuur.prijzen', []);
    }

    public static function getPrijzenString()
    {
        $year = date("Y");
        $prijzen = self::getPrijzenPerJaar();
        if (!isset($prijzen[$year])) {
            return 'De huurprijs is onbekend door een technische fout';
        }
        $current_price = '€ ' . $prijzen[$year];

        $other = '';
        $i = $year + 1;
        while (isset($prijzen[$i])) {
            if ($prijzen[$i] == $prijzen[$year]) {
                $i++;
                continue;
            }
            if ($other != '') {
                $other .= ', ';
            } else {
                $other .= ' (';
            }
            $other .= $i . ': € ' . $prijzen[$i];
            $i++;
        }
        if ($other != '') {
            $other .= ')';
        }

        return "$current_price / nacht voor verblijven in $year$other";
    }

    public function getExcelSafeTelephone()
    {
        return str_replace(" ", " ", $this->contact_gsm);
    }

    public function getWaarborg($excelsafe = false)
    {
        if ($excelsafe) {
            return 'EUR ' . money_format('%!.2n', $this->waarborg);
        }
        return '€ ' . money_format('%!.2n', $this->waarborg);
    }

    public function getHuur($excelsafe = false)
    {
        if ($excelsafe) {
            return 'EUR ' . money_format('%!.2n', $this->huur);
        }
        return '€ ' . money_format('%!.2n', $this->huur);
    }

    public function calculateHuur()
    {
        $difference = $this->startdatum->diff($this->einddatum);
        $days = $difference->d;

        $plus = 0;
        if ($this->personen_tenten > 0) {
            $plus += Environment::getSetting('verhuur.prijs_tent_nacht', 0) * $days + Environment::getSetting('verhuur.prijs_tent_persoon', 0) * $this->personen_tenten * $days;
        }

        $plus += $days * Environment::getSetting('verhuur.prijs_extra_persoon_gebouw', 0) * max(0, $this->personen - Environment::getSetting('verhuur.prijs_inbegrepen_personen', 0));

        $jaar = $this->startdatum->format('Y');
        $prijzen = self::getPrijzenPerJaar();

        if (isset($prijzen[$jaar])) {
            return $prijzen[$jaar] * $days + $plus;
        }

        return $plus;
    }

    public static function js_calculateHuur()
    {
        // function calculateHuurPrijs(startdate, enddate, diffDays, persons, persons_tenten)
        return '
        var prices = ' . json_encode(self::getPrijzenPerJaar()) . ';
        var inbegrepen = ' . json_encode(Environment::getSetting('verhuur.prijs_inbegrepen_personen', 0)) . ';
        var prijs_extra_persoon_gebouw = ' . json_encode(Environment::getSetting('verhuur.prijs_extra_persoon_gebouw', 0)) . ';

        var base_price = diffDays * prices[startdate.getFullYear()];
        if (persons_tenten > 0) {
            base_price += persons_tenten * ' . Environment::getSetting('verhuur.prijs_tent_persoon', 0) . ' * diffDays + ' . Environment::getSetting('verhuur.prijs_tent_nacht', 0) . ' * diffDays;
        }
        if (persons > inbegrepen) {
            base_price += diffDays * (persons - inbegrepen) * prijs_extra_persoon_gebouw;
        }
        return base_price;';
    }

    public function calculateWaarborg()
    {
        $difference = $this->startdatum->diff($this->einddatum);
        $days = $difference->d;

        if ($days <= 2) {
            if ($this->leidingsweekend) {
                return Environment::getSetting('verhuur.waarborg_weekend_leiding', Environment::getSetting('verhuur.waarborg_weekend', 0));
            }
            return Environment::getSetting('verhuur.waarborg_weekend', 0);
        }
        return Environment::getSetting('verhuur.waarborg_kamp', 0);
    }

    public static function js_calculateBorg()
    {
        // function calculateBorg(startdate, enddate, diffDays, persons, persons_tenten)
        return '
        var borg = ' . Environment::getSetting('verhuur.waarborg_weekend', 0) . ';

        if (diffDays > 2) {
            borg = ' . Environment::getSetting('verhuur.waarborg_kamp', 0) . ';
        }
        return borg;';
    }

    //public $prijzen = array(2016 => );

    public function __construct($row = array())
    {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['id'];
        $this->contract_nummer = $row['contract_nummer'];
        $this->startdatum = new \DateTime($row['startdatum']);
        $this->einddatum = new \DateTime($row['einddatum']);

        $this->door_leiding = ($row['door_leiding'] == 1);

        $this->personen = $row['personen'];
        $this->personen_tenten = $row['personen_tenten'];

        $this->groep = $row['groep'];
        $this->contact_naam = $row['contact_naam'];
        $this->contact_email = $row['contact_email'];
        $this->contact_gsm = $row['contact_gsm'];

        $this->contact_adres = $row['contact_adres'];
        $this->contact_gemeente = $row['contact_gemeente'];
        $this->contact_postcode = $row['contact_postcode'];
        $this->contact_land = $row['contact_land'];

        $this->info = $row['info'];
        $this->opmerkingen = $row['opmerkingen'];

        $this->waarborg = $row['waarborg'];
        $this->huur = $row['huur'];

        $this->ligt_vast = ($row['ligt_vast'] == 1);

        $this->waarborg_betaald = ($row['waarborg_betaald'] == 1);
        $this->huur_betaald = ($row['huur_betaald'] == 1);
        $this->leidingsweekend = ($row['leidingsweekend'] == 1);
        $this->contract_oke = ($row['contract_oke'] == 1);

        $this->aanvraag_datum = new \DateTime($row['aanvraag_datum']);

    }

    // Beide grensen inclusief
    // startdate en enddate in y-m-d formaat
    public static function getReservaties($startdate, $enddate, $ligt_vast = null, $not_include_id = null, $exclude_border = false)
    {
        $ligt_vast_str = '';
        if (!is_null($ligt_vast)) {
            $ligt_vast_str = ' AND ligt_vast = "' . self::getDb()->escape_string($ligt_vast) . '"';
        }
        $not_include_str = '';
        if (!is_null($not_include_id)) {
            $not_include_str = ' AND id <> "' . self::getDb()->escape_string($not_include_id) . '"';
        }

        $startdate = self::getDb()->escape_string($startdate);
        $enddate = self::getDb()->escape_string($enddate);

        $st = '<=';
        $gt = '>=';
        if ($exclude_border) {
            $st = '<';
            $gt = '>';
        }

        $reservaties = array();
        $query = "SELECT * FROM verhuur WHERE ((startdatum >= '$startdate' AND startdatum $st '$enddate') OR (einddatum $gt '$startdate' AND einddatum <= '$enddate') OR (startdatum <= '$startdate' AND einddatum >= '$enddate'))$ligt_vast_str$not_include_str ORDER BY startdatum";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reservaties[] = new Reservatie($row);
                }
            }
        }
        return $reservaties;
    }

    public static function getReservatiesOverview($future_only = true)
    {
        $reservaties = array();

        if ($future_only) {
            $query = 'SELECT * FROM verhuur WHERE einddatum >= DATE_FORMAT(CURDATE(),\'%Y-%m-01\') ORDER BY startdatum';
        } else {
            $query = 'SELECT * FROM verhuur ORDER BY startdatum';
        }

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $reservaties[] = new Reservatie($row);
                }
            }
        }
        echo self::getDb()->error;
        return $reservaties;
    }

    public static function getAantalInWeek($prefix)
    {
        $prefix = self::getDb()->escape_string($prefix);
        $query = 'SELECT * FROM verhuur WHERE contract_nummer is not null AND contract_nummer LIKE \''.$prefix.'%\'';

        if ($result = self::getDb()->query($query)) {
            return $result->num_rows;
        }
        return 0;
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    public static function getReservatie($id)
    {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);
        $query = 'SELECT * FROM verhuur WHERE id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return new Reservatie($row);
            }
        }
        return null;
    }

    public static function getReservatieForContractNummer($contract_nummer)
    {
        $contract_nummer = self::getDb()->escape_string($contract_nummer);
        $query = 'SELECT * FROM verhuur WHERE contract_nummer = "' . $contract_nummer . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return new Reservatie($row);
            }
        }

        return null;
    }

    public function setProperties(&$data, $basic = false)
    {
        $admin = false;
        if (isset($data['huur']) && Leiding::isLoggedIn() && (Leiding::hasPermission('verhuur') || Leiding::hasPermission('oudercomite'))) {
            $admin = true;
            $this->no_mail = true;
        }

        $errors = array();

        // Startdatum
        $startdatum = \DateTime::createFromFormat('d-m-Y', $data['startdatum']);
        if ($startdatum !== false) {
            $this->startdatum = clone $startdatum;
            $data['startdatum'] = $startdatum->format('d-m-Y');
        } else {
            $errors[] = 'Ongeldige begindatum';
        }

        // einddatum
        $einddatum = \DateTime::createFromFormat('d-m-Y', $data['einddatum']);
        if ($einddatum !== false) {
            $this->einddatum = clone $einddatum;
            $data['einddatum'] = $einddatum->format('d-m-Y');
        } else {
            $errors[] = 'Ongeldige einddatum';
        }

        if ($admin && isset($data["door_leiding"]) && $data["door_leiding"] === true) {
            $this->door_leiding = true;
            $this->ligt_vast = true;
            $this->groep = ucfirst($data['door_leiding_reden']);

            $data['door_leiding_reden'] = $this->groep;
            return $errors;
        } else {
            $this->door_leiding = null;
        }

        // Ligt vast moet VOOR start en eind controle liggen!
        // hier ligt_vast setten etc

        $personen = intval($data['personen']);
        if ($personen < 1 || $personen > Environment::getSetting('verhuur.max_gebouw', 0)) {
            $errors[] = 'Ongeldig aantal personen';
        } else {
            $data['personen'] = $personen;
            $this->personen = $personen;
        }

        $personen_tenten = intval($data['personen_tenten']);
        if ($personen_tenten < 0 || $personen_tenten > Environment::getSetting('verhuur.max_tenten', 0)) {
            $errors[] = 'Ongeldig aantal personen in tenten';
        } else {
            $ok = true;
            if ($startdatum !== false && $einddatum !== false) {
                $difference = $this->startdatum->diff($this->einddatum);
                $days = $difference->d;

                if ($days < Environment::getSetting('verhuur.tenten_min_nachten', 0)) {
                    if ($admin && $personen_tenten > 0) {
                        $errors[] = 'Het is niet mogelijk om tenten te zetten bij verhuur van minder dan ' . Environment::getSetting('verhuur.tenten_min_nachten', 0) . ' nachten.';
                    }

                    $data['personen_tenten'] = 0;
                    $this->personen_tenten = 0;

                    $ok = false;
                }
            }

            if ($ok) {
                $data['personen_tenten'] = $personen_tenten;
                $this->personen_tenten = $personen_tenten;
            }
        }

        if (!$basic) {
            if (isset($data['leidingsweekend']) && $data['leidingsweekend']) {
                $this->leidingsweekend = true;
            } else {
                $this->leidingsweekend = false;
            }
        }

        if ($admin && !$basic) {
            if (!$data['waarborg_betaald']) {
                $this->waarborg_betaald = false;
            } else {
                $this->waarborg_betaald = true;
            }

            if (!$data['huur_betaald']) {
                $this->huur_betaald = false;
            } else {
                $this->huur_betaald = true;
            }

            if (!$data['ligt_vast']) {
                $this->ligt_vast = false;
            } else {
                $this->ligt_vast = true;
            }

            if (!isset($data['contract_oke']) || !$data['contract_oke']) {
                $this->contract_oke = false;
            } else {
                $this->contract_oke = true;
            }
        }

        // Als nieuw: controleren of er al niet vaststaande verhuren zijn op deze datums
        if ((empty($this->id) || $this->ligt_vast) && $startdatum !== false && $einddatum !== false) {
            if (!empty($this->id)) {
                $reservaties = self::getReservaties($startdatum->format('Y-m-d'), $einddatum->format('Y-m-d'), 1, $this->id, $admin || Environment::getSetting('verhuur.overlapping_grenzen_toelaten'));
            } else {
                $reservaties = self::getReservaties($startdatum->format('Y-m-d'), $einddatum->format('Y-m-d'), 1, null, $admin || Environment::getSetting('verhuur.overlapping_grenzen_toelaten'));
            }
            if (count($reservaties) > 0) {
                $errors[] = 'Er ligt al een reservatie vast in deze periode.';
            }
        }

        if ($basic) {
            return $errors;
        }

        if (Validator::isValidGroupName($data['groep'])) {
            $this->groep = ucfirst($data['groep']);
            $data['groep'] = $this->groep;
        } else {
            $errors[] = 'Ongeldige groepsnaam, vermijd speciale tekens';
        }

        if (!$admin) {
            if (Validator::isValidName($data['contact_naam'])) {
                $this->contact_naam = ucfirst($data['contact_naam']);
                $data['contact_naam'] = $this->contact_naam;
            } else {
                $errors[] = 'Contactpersoon naam is ongeldig';
            }

            if (Validator::isValidMail($data['contact_email'])) {
                $this->contact_email = strtolower($data['contact_email']);
                $data['contact_email'] = $this->contact_email;
            } else {
                $errors[] = 'Contactpersoon e-mailadres is ongeldig. Kijk het letter per letter na a.u.b. (komma\'s ipv punten...)';
            }

            Validator::validateBothPhone($data['contact_gsm'], $this->contact_gsm, $errors);

            if (Validator::isValidAddress($data['contact_adres'])) {
                $this->contact_adres = ucwords(mb_strtolower($data['contact_adres']));
                $data['contact_adres'] = $this->contact_adres;
            } else {
                $errors[] = 'Ongeldig adres';
            }

            if (strlen($data['contact_land']) < 4) {
                $errors[] = 'Ongeldig land';
            } else {
                $this->contact_land = ucwords(mb_strtolower($data['contact_land']));
                if ($this->contact_land == "Belgie" || $this->contact_land == "Belgium" || $this->contact_land == "Belgique") {
                    $this->contact_land = "België";
                }

                $data['contact_land'] = $this->contact_land;

                if ($this->contact_land == "België") {
                    if (Adres::validateGemeente($data['contact_gemeente'], $data['contact_postcode'], $errors)) {
                        $this->contact_gemeente = $data['contact_gemeente'];
                        $this->contact_postcode = $data['contact_postcode'];
                    }
                } else {
                    if (strlen($data['contact_gemeente']) < 4) {
                        $errors[] = 'Ongeldige gemeente';
                    } else {
                        $this->contact_gemeente = ucwords(mb_strtolower($data['contact_gemeente']));
                        $data['contact_gemeente'] = $this->contact_gemeente;
                    }
                    if (strlen($data['contact_postcode']) < 2) {
                        $errors[] = 'Ongeldige postcode';
                    } else {
                        $this->contact_postcode = ucwords(mb_strtolower($data['contact_postcode']));
                        $data['contact_postcode'] = $this->contact_postcode;
                    }

                }
            }

            if (strlen($data['info']) < 10) {
                $errors[] = 'Geef wat meer info over jouw groep.';
            } else {
                $data['info'] = ucsentence($data['info']);
                $this->info = $data['info'];
            }
        } else {
            $this->contact_naam = ucfirst($data['contact_naam']);
            $data['contact_naam'] = $this->contact_naam;

            $this->contact_email = strtolower($data['contact_email']);
            $data['contact_email'] = $this->contact_email;

            if (strlen(trim($data['contact_gsm'])) > 0) {
                Validator::validateBothPhone($data['contact_gsm'], $this->contact_gsm, $errors);
            } else {
                $this->contact_gsm = '';
            }

            $this->contact_adres = ucwords(mb_strtolower($data['contact_adres']));
            $data['contact_adres'] = $this->contact_adres;

            $this->contact_land = ucwords(mb_strtolower($data['contact_land']));
            if ($this->contact_land == "Belgie" || $this->contact_land == "Belgium" || $this->contact_land == "Belgique") {
                $this->contact_land = "België";
            }

            $data['contact_land'] = $this->contact_land;

            $this->contact_gemeente = ucwords(mb_strtolower(trim($data['contact_gemeente'])));
            $data['contact_gemeente'] = $this->contact_gemeente;

            $this->contact_postcode = ucwords(mb_strtolower(trim($data['contact_postcode'])));
            $data['contact_postcode'] = $this->contact_postcode;

            $data['info'] = ucsentence($data['info']);
            $this->info = $data['info'];
        }

        $data['opmerkingen'] = ucsentence($data['opmerkingen']);
        $this->opmerkingen = $data['opmerkingen'];

        // Extra instellingen (prijs etc)
        if (!$admin) {
            return $errors;
        }

        Validator::validatePrice($data['huur'], $this->huur, $errors);
        Validator::validatePrice($data['waarborg'], $this->waarborg, $errors);

        return $errors;
    }

    // Slaat het object op (of voegt het toe)
    // En stelt huur en waarborg ook juist in, stuurt evt mail naar verantwoordelijke
    public function save()
    {

        $startdatum = self::getDb()->escape_string($this->startdatum->format('Y-m-d'));
        $einddatum = self::getDb()->escape_string($this->einddatum->format('Y-m-d'));

        $groep = self::getDb()->escape_string($this->groep);
        $ligt_vast = self::getDb()->escape_string((int) $this->ligt_vast);
        $leidingsweekend = self::getDb()->escape_string((int) $this->leidingsweekend);
        $contract_oke = self::getDb()->escape_string((int) $this->contract_oke);

        if (!isset($this->id)) {
            $this->aanvraag_datum = new \DateTime();
            $aanvraag_datum = self::getDb()->escape_string($this->aanvraag_datum->format('Y-m-d'));
        }

        if (isset($this->door_leiding) && $this->door_leiding === true) {
            // Simpel opslaan, enkel start, einde, leiding, groep + ligt_vast
            $door_leiding = 1;
            $ligt_vast = 1;
            $leidingsweekend = 0;
            $contract_oke = 1;

            if (!isset($this->id)) {
                $query = "INSERT INTO
                    verhuur (`startdatum`,  `einddatum`, `door_leiding`, `groep`, `ligt_vast`, `aanvraag_datum`, `leidingsweekend`, `contract_oke`)
                    VALUES ('$startdatum', '$einddatum', '$door_leiding', '$groep', '$ligt_vast', '$aanvraag_datum', '$leidingsweekend', '$contract_oke')";
            } else {
                $id = self::getDb()->escape_string($this->id);
                $query = "UPDATE verhuur
                    SET
                     `startdatum` = '$startdatum',
                     `einddatum` = '$einddatum',
                     `door_leiding` = '$door_leiding',
                     `groep` = '$groep',
                     `ligt_vast` = '$ligt_vast',
                     `leidingsweekend` = '$leidingsweekend',
                     `contract_oke` = '$contract_oke'
                     where id = '$id'
                ";
            }
        } else {
            $personen = self::getDb()->escape_string($this->personen);
            $personen_tenten = self::getDb()->escape_string($this->personen_tenten);
            $contact_naam = self::getDb()->escape_string($this->contact_naam);
            $contact_email = self::getDb()->escape_string($this->contact_email);
            $contact_gsm = self::getDb()->escape_string($this->contact_gsm);
            $contact_adres = self::getDb()->escape_string($this->contact_adres);
            $contact_gemeente = self::getDb()->escape_string($this->contact_gemeente);
            $contact_postcode = self::getDb()->escape_string($this->contact_postcode);
            $contact_land = self::getDb()->escape_string($this->contact_land);

            $info = self::getDb()->escape_string($this->info);
            $opmerkingen = self::getDb()->escape_string($this->opmerkingen);

            if (!isset($this->id)) {
                $this->waarborg = $this->calculateWaarborg();
                $this->huur = $this->calculateHuur();
            }

            $waarborg = self::getDb()->escape_string($this->waarborg);
            $huur = self::getDb()->escape_string($this->huur);

            $waarborg_betaald = self::getDb()->escape_string((int) $this->waarborg_betaald);
            $huur_betaald = self::getDb()->escape_string((int) $this->huur_betaald);

            if (!isset($this->contract_nummer)) {
                $prefix = substr($this->startdatum->format('o'), 2) . $this->startdatum->format('W');
                $aantal = self::getAantalInWeek($prefix) + 1;
                $this->contract_nummer = $prefix . '-' . $aantal;
            }

            if (!isset($this->contract_nummer)) {
                $contract_nummer = 'NULL';
            } else {
                $contract_nummer = "'" . self::getDb()->escape_string($this->contract_nummer) . "'";
            }

            if (empty($this->id)) {

                $query = "INSERT INTO
                    verhuur (`contract_nummer`, `door_leiding`, `startdatum`,`einddatum`,`personen`,`personen_tenten`,`groep`,`contact_naam`,`contact_email`,`contact_gsm`,`contact_adres`,`contact_gemeente`,`contact_postcode`,`contact_land`,`info`,`opmerkingen`,`waarborg`,`huur`,`ligt_vast`, `waarborg_betaald`, `huur_betaald`, `aanvraag_datum`, `leidingsweekend`, `contract_oke`)

                    VALUES ($contract_nummer, NULL, '$startdatum','$einddatum','$personen','$personen_tenten','$groep','$contact_naam','$contact_email','$contact_gsm','$contact_adres','$contact_gemeente','$contact_postcode','$contact_land','$info','$opmerkingen', '$waarborg', '$huur', '$ligt_vast', '$waarborg_betaald', '$huur_betaald', '$aanvraag_datum', '$leidingsweekend', '$contract_oke')";
            } else {
                $id = self::getDb()->escape_string($this->id);

                $query = "UPDATE verhuur
                    SET
                        `contract_nummer` = $contract_nummer,
                        `door_leiding` = NULL,
                        `startdatum` = '$startdatum',
                        `einddatum` = '$einddatum',
                        `personen` = '$personen',
                        `personen_tenten` = '$personen_tenten',
                        `groep` = '$groep',
                        `contact_naam` = '$contact_naam',
                        `contact_email` = '$contact_email',
                        `contact_gsm` = '$contact_gsm',
                        `contact_adres` = '$contact_adres',
                        `contact_gemeente` = '$contact_gemeente',
                        `contact_postcode` = '$contact_postcode',
                        `contact_land` = '$contact_land',
                        `info` = '$info',
                        `opmerkingen` = '$opmerkingen',
                        `waarborg` = '$waarborg',
                        `huur` = '$huur',
                        `ligt_vast` = '$ligt_vast',
                        `waarborg_betaald` = '$waarborg_betaald',
                        `huur_betaald` = '$huur_betaald',
                        `leidingsweekend` = '$leidingsweekend',
                        `contract_oke` = '$contract_oke'
                     where id = '$id'
                ";
            }

        }

        if (self::getDb()->query($query)) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;

                if (!isset($this->door_leiding) && !$this->no_mail) {

                    // Mail voor verantwoordelijke(n)
                    $mail = Mail::create('Huur aanvraag van ' . $this->groep, 'verhuurder-aanvraag', array('reservatie' => $this));
                    $verhuurder = null;

                    foreach (Leiding::getContactEmails('verhuur') as $contact) {
                        if (!isset($verhuurder)) {
                            $verhuurder = $contact->mail;
                        }

                        $mail->addTo(
                            $contact->mail,
                            array(),
                            $contact->name
                        );
                    }

                    $mail->sendOrDelay();

                    // Andere (mail voor huurder zelf)
                    $mail = Mail::create('Verhuur aanvraag', 'huurder-aanvraag', array('reservatie' => $this));

                    $mail->addTo(
                        $this->contact_email,
                        array(),
                        $this->contact_naam
                    );

                    $mail->setReplyTo($verhuurder);

                    $mail->sendOrDelay();

                }

            }
            return true;
        }
        echo $query;
        echo self::getDb()->error;
        return false;
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                verhuur WHERE id = '$id' ";

        return self::getDb()->query($query);
    }

    public function getTitle()
    {
        if ($this->door_leiding) {
            return "[Vrijgehouden] " . $this->groep;
        }
        return "[" . $this->contract_nummer . "] " . $this->groep;
    }

    public function getDescription()
    {
        $extra = '';

        if ($this->leidingsweekend) {
            $extra = "Leidingsweekend";
        }

        if ($this->door_leiding) {
            return "Vrijgehouden voor scouts. " . $extra;
        }

        if (!$this->contract_oke) {
            return "Contract niet in orde. " . $extra;
        }
        if (!$this->waarborg_betaald && !$this->huur_betaald) {
            return "Huur + waarborg niet betaald. " . $extra;
        }
        if (!$this->huur_betaald) {
            return "Huur niet betaald. " . $extra;
        }
        if (!$this->waarborg_betaald) {
            return "Waarborg niet betaald. " . $extra;
        }
        return $extra;
    }
}
