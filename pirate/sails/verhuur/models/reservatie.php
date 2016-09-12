<?php
namespace Pirate\Model\Verhuur;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Mail\Mail;
use Pirate\Model\Leiding\Leiding;

class Reservatie extends Model {
    public $id;
    public $contract_nummer;
    public $startdatum;
    public $einddatum;

    public $leiding; // voorlopig geowoon id, later Leidings object of null

    public $personen;
    public $personen_tenten;

    public $groep;
    public $contact_naam;
    public $contact_email;
    public $contact_gsm;
    public $info;

    public $opmerkingen;

    public $waarborg;
    public $huur;

    public $ligt_vast = false; // true / false
    public $contract_ondertekend = false; // true / false
    public $waarborg_betaald = false; // true / false
    public $huur_betaald = false; // true / false
    public $waarborg_ingetrokken = 0; // Null (= nog niet afgehandeld) of getal (0 - ...)
    public $goedgekeurd = null; // null, true of false

    static public $max_gebouw = 40;
    static public $max_tenten = 20;

    static public $prijzen = array(2016 => 95, 2017 => 98, 2018 => 100);

    function calculateWaarborg() {
        $difference = $this->startdatum->diff($this->einddatum);
        $days = $difference->d;

        if ($days <= 2) {
            return 400;
        }
        return 750;
    }

    function getWaarborg() {
        return '€ '.money_format('%!.2n', $this->waarborg);
    }

    function getHuur() {
        return '€ '.money_format('%!.2n', $this->huur);
    }

    function getWaarborgIngetrokken() {
        if (empty($this->waarborg_ingetrokken)) {
            return '';
        }
        return '€ '.money_format('%!.2n', $this->waarborg_ingetrokken);
    }

    function calculateHuur() {
        $difference = $this->startdatum->diff($this->einddatum);
        $days = $difference->d;

        $plus = 0;
        if ($this->personen_tenten > 0) {
            $plus += 15*$days + 2*$this->personen_tenten*$days;
        }


        $jaar = $this->startdatum->format('Y');
        if (isset(self::$prijzen[$jaar])) {
            return self::$prijzen[$jaar]*$days + $plus;
        }

        return $plus;
    }

    static public function js_calculateHuur() {
        // function calculateHuurPrijs(startdate, enddate, diffDays, persons, persons_tenten)
        return '
        var prices = '.json_encode(self::$prijzen).';

        var base_price = diffDays * prices[startdate.getFullYear()];
        if (persons_tenten > 0) {
            base_price += persons_tenten*2*diffDays + 15*diffDays;
        }
        return base_price;';
    }

    static public function js_calculateBorg() {
        // function calculateBorg(startdate, enddate, diffDays, persons, persons_tenten)
        return '
        var borg = 400;

        if (diffDays > 2) {
            borg = 750;
        }
        return borg;';
    }

    //public $prijzen = array(2016 => );

    function __construct($row = array()) {
        if (count($row) == 0){
            return;
        }

        $this->id = $row['id'];
        $this->contract_nummer = $row['contract_nummer'];
        $this->startdatum = new \DateTime($row['startdatum']);
        $this->einddatum = new \DateTime($row['einddatum']);

        $this->leiding = $row['leiding_id'];

        $this->leiding = $row['leiding_id'];
        $this->personen = $row['personen'];
        $this->personen_tenten = $row['personen_tenten'];

        $this->groep = $row['groep'];
        $this->contact_naam = $row['contact_naam'];
        $this->contact_email = $row['contact_email'];
        $this->contact_gsm = $row['contact_gsm'];
        $this->info = $row['info'];
        $this->opmerkingen = $row['opmerkingen'];

        $this->waarborg = $row['waarborg'];
        $this->huur = $row['huur'];

        $this->ligt_vast = ($row['ligt_vast'] == 1);
        $this->contract_ondertekend = ($row['contract_ondertekend'] == 1);

        $this->waarborg_betaald = ($row['waarborg_betaald'] == 1);
        $this->huur_betaald = ($row['huur_betaald'] == 1);
        $this->waarborg_ingetrokken = $row['waarborg_ingetrokken'];

        if (!isset($row['goedgekeurd'])) {
            $this->goedgekeurd =  null;
        } else {
            $this->goedgekeurd = ($row['goedgekeurd'] == 1);
        }

    }


    // Beide grensen inclusief
    // startdate en enddate in y-m-d formaat
    static function getReservaties($startdate, $enddate, $ligt_vast = null, $not_include = null) {
        $ligt_vast_str = '';
        if (!is_null($ligt_vast)) {
            $ligt_vast_str = ' AND ligt_vast = "'.self::getDb()->escape_string($ligt_vast).'"';
        }
        $not_include_str = '';
        if (!is_null($not_include)) {
            $not_include_str = ' AND id <> "'.self::getDb()->escape_string($not_include).'"';
        }

        $startdate = self::getDb()->escape_string($startdate);
        $enddate = self::getDb()->escape_string($enddate);

        $reservaties = array();
        $query = 'SELECT * FROM verhuur WHERE ((startdatum >= "'.$startdate.'" AND startdatum <= "'.$enddate.'") OR (einddatum >= "'.$startdate.'" AND einddatum <= "'.$enddate.'") OR (startdatum <= "'.$startdate.'" AND einddatum >= "'.$enddate.'"))'.$ligt_vast_str.$not_include_str.' ORDER BY startdatum';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $reservaties[] = new Reservatie($row);
                }
            }
        }
        return $reservaties;
    }

    static function getReservatiesOverview() {
        $reservaties = array();
        $query = 'SELECT * FROM verhuur WHERE goedgekeurd IS NULL OR goedgekeurd = 1 ORDER BY (goedgekeurd is null) desc, (ligt_vast = 0) desc, startdatum';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $reservaties[] = new Reservatie($row);
                }
            }
        }
        return $reservaties;
    }

    static function getAantalInWeek($week) {
        $week = self::getDb()->escape_string($week);
        $query = 'SELECT * FROM verhuur WHERE week(startdatum, 3) = "'.$week.'" and contract_nummer is not null';

        if ($result = self::getDb()->query($query)){
            return $result->num_rows;
        }
        return 0;
    }

    // Maximaal 30 events! Rest wordt weg geknipt
    static function getReservatie($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);
        $query = 'SELECT * FROM verhuur WHERE id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $row = $result->fetch_assoc();
                return new Reservatie($row);
            }
        }
        return null;
    }

    static function getReservatieForContractNummer($contract_nummer) {
        $contract_nummer = self::getDb()->escape_string($contract_nummer);
        $query = 'SELECT * FROM verhuur WHERE contract_nummer = "'.$contract_nummer.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                $row = $result->fetch_assoc();
                return new Reservatie($row);
            }
        }

        return null;
    }

    function setProperties(&$data, $basic = false) {
        $admin = false;
        if (isset($data['huur']) && Leiding::isLoggedIn() && Leiding::hasPermission('verhuur')) {
            $admin = true;
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

        // Ligt vast moet VOOR start en eind controle liggen!
        // hier ligt_vast setten etc

        $personen = intval($data['personen']);
        if ($personen < 1 || $personen > self::$max_gebouw) {
            $errors[] = 'Ongeldig aantal personen';
        } else {
            $data['personen'] = $personen;
            $this->personen = $personen;
        }

        $personen_tenten = intval($data['personen_tenten']);
        if ($personen_tenten < 0 || $personen_tenten > self::$max_tenten) {
            $errors[] = 'Ongeldig aantal personen in tenten';
        } else {
            $ok = true;
            if ($startdatum !== false && $einddatum !== false) {
                $difference = $this->startdatum->diff($this->einddatum);
                $days = $difference->d;

                if ($days <= 2) {
                    if ($admin && $personen_tenten > 0) {
                        $errors[] = 'Het is niet mogelijk om tenten te zetten bij overnachtingen van 2 nachten of minder.';
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

        // Nu alle checkboxen! VOOR checken datum periode
        if (isset($data['ligt_vast']) && $data['ligt_vast']) {
            $this->ligt_vast = true;
        } else {
            $this->ligt_vast = false;
        }

        if ($admin && !$basic) {
            // Als goedgekeurd == '' of 0 => ligt_vast op 0 zetten
            if ($data['goedgekeurd'] === false || $data['goedgekeurd'] === true) {
                $this->goedgekeurd = $data['goedgekeurd'];

                if (!$data['contract_ondertekend']) {
                    $this->contract_ondertekend = false;
                } else {
                    $this->contract_ondertekend = true;
                }

                if (!$data['waarborg_betaald']) {
                    $this->waarborg_betaald = false;
                } else {
                    $this->waarborg_betaald = true;
                    Validator::validatePrice($data['waarborg_ingetrokken'], $this->waarborg_ingetrokken, $errors);
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

                

            } else {
                $this->goedgekeurd = null;
                //  ligt_vast op 0 zetten
                $this->ligt_vast = 0;
            }
        }



        // Als nieuw: controleren of er al niet vaststaande verhuren zijn op deze datums
        if ((empty($this->id) || $this->ligt_vast || $this->goedgekeurd) && $startdatum !== false && $einddatum !== false) {
            if (!empty($this->id)) {
                $reservaties = self::getReservaties($startdatum->format('Y-m-d'), $einddatum->format('Y-m-d'), 1, $this->id);
            } else {
                $reservaties = self::getReservaties($startdatum->format('Y-m-d'), $einddatum->format('Y-m-d'), 1);
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
            $errors[] = 'Contactpersoon e-mailadres is ongeldig';
        }

        Validator::validateBothPhone($data['contact_gsm'], $this->contact_gsm, $errors);

        if (strlen($data['info']) < 10) {
            $errors[] = 'Geef wat meer info over jouw groep.';
        } else {
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
    function save() {

        $startdatum = self::getDb()->escape_string($this->startdatum->format('Y-m-d'));
        $einddatum = self::getDb()->escape_string($this->einddatum->format('Y-m-d'));

        $groep = self::getDb()->escape_string($this->groep);
        $ligt_vast = self::getDb()->escape_string((int) $this->ligt_vast);

        if (isset($this->leiding)) {
            // Simpel opslaan, enkel start, einde, leiding, groep + ligt_vast
            $leiding_id = self::getDb()->escape_string($this->leiding);

            if (!isset($this->id)) {
                $query = "INSERT INTO 
                    verhuur (`startdatum`,  `einddatum`, `leiding_id`, `groep`, `ligt_vast`)
                    VALUES ('$startdatum', '$einddatum', '$leiding_id', '$groep', '$ligt_vast')";
            } else {
                $id = self::getDb()->escape_string($this->id);
                $query = "UPDATE verhuur 
                    SET 
                     `startdatum` = '$startdatum',
                     `einddatum` = '$einddatum',
                     `leiding_id` = '$leiding_id',
                     `groep` = $groep,
                     `ligt_vast` = '$ligt_vast'
                     where id = '$id' 
                ";
            }
        } else {
            $personen = self::getDb()->escape_string($this->personen);
            $personen_tenten = self::getDb()->escape_string($this->personen_tenten);
            $contact_naam = self::getDb()->escape_string($this->contact_naam);
            $contact_email = self::getDb()->escape_string($this->contact_email);
            $contact_gsm = self::getDb()->escape_string($this->contact_gsm);

            $info = self::getDb()->escape_string($this->info);
            $opmerkingen = self::getDb()->escape_string($this->opmerkingen);

            if (!isset($this->id)) {
                $this->waarborg = $this->calculateWaarborg();
                $this->huur = $this->calculateHuur();
            }

            $waarborg = self::getDb()->escape_string($this->waarborg);
            $huur = self::getDb()->escape_string($this->huur);

            $contract_ondertekend = self::getDb()->escape_string((int) $this->contract_ondertekend);
            $waarborg_betaald = self::getDb()->escape_string((int) $this->waarborg_betaald);
            $huur_betaald = self::getDb()->escape_string((int) $this->huur_betaald);
            $waarborg_ingetrokken = self::getDb()->escape_string($this->waarborg_ingetrokken);

            if (empty($this->id)) {
                $query = "INSERT INTO 
                    verhuur (`startdatum`,`einddatum`,`personen`,`personen_tenten`,`groep`,`contact_naam`,`contact_email`,`contact_gsm`,`info`,`opmerkingen`,`waarborg`,`huur`,`ligt_vast`,  `contract_ondertekend`, `waarborg_betaald`, `huur_betaald`, `waarborg_ingetrokken`)
                    
                    VALUES ('$startdatum','$einddatum','$personen','$personen_tenten','$groep','$contact_naam','$contact_email','$contact_gsm','$info','$opmerkingen', '$waarborg', '$huur', '$ligt_vast', '$contract_ondertekend', '$waarborg_betaald', '$huur_betaald', '$waarborg_ingetrokken')";
            } else {
                $id = self::getDb()->escape_string($this->id);

                if (!isset($this->contract_nummer) && $this->goedgekeurd === true) {
                    $aantal = self::getAantalInWeek($this->startdatum->format('W')) + 1;
                    $this->contract_nummer = substr($this->startdatum->format('o'), 2).$this->startdatum->format('W').'-'.$aantal;
                }

                if (!isset($this->contract_nummer)) {
                    $contract_nummer = 'NULL';
                }
                else {
                    $contract_nummer = "'".self::getDb()->escape_string($this->contract_nummer)."'";
                }

                $goedgekeurd = 'NULL';
                if (isset($this->goedgekeurd)) {
                    $goedgekeurd = "'".self::getDb()->escape_string((int) $this->goedgekeurd)."'";
                }

                $query = "UPDATE verhuur 
                    SET 
                        `contract_nummer` = $contract_nummer,
                        `startdatum` = '$startdatum',
                        `einddatum` = '$einddatum',
                        `personen` = '$personen',
                        `personen_tenten` = '$personen_tenten',
                        `groep` = '$groep',
                        `contact_naam` = '$contact_naam',
                        `contact_email` = '$contact_email',
                        `contact_gsm` = '$contact_gsm',
                        `info` = '$info',
                        `opmerkingen` = '$opmerkingen',
                        `waarborg` = '$waarborg',
                        `huur` = '$huur',
                        `ligt_vast` = '$ligt_vast',
                        `contract_ondertekend` = '$contract_ondertekend',
                        `waarborg_betaald` = '$waarborg_betaald',
                        `huur_betaald` = '$huur_betaald',
                        `waarborg_ingetrokken` = '$waarborg_ingetrokken',
                        `goedgekeurd` = $goedgekeurd
                     where id = '$id' 
                ";
            }
            
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;

                if (empty($this->leiding)) {
                    // Mail voor verantwoordelijke
                    
                    $leiding = Leiding::getLeiding('verhuur');
                    if (count($leiding) > 0) {
                        $mail = new Mail('Huur aanvraag van '.$this->groep, 'verhuurder-aanvraag', array('reservatie' => $this));

                        foreach ($leiding as $l) {
                            $mail->addTo(
                                $l->mail, 
                                array(),
                                $l->firstname.' '.$l->lastname
                            );
                        }

                        $mail->send();
                    }

                    // Andere
                    $mail = new Mail('Verhuur aanvraag', 'huurder-aanvraag', array('reservatie' => $this));

                    $mail->addTo(
                        $this->contact_email, 
                        array(),
                        $this->contact_naam
                    );

                    $mail->send();

                } else {
                    // TODO: Mail sturen naar verantwoordelijke voor aanvraag van de leiding!
                }

            }
            return true;
        }
        echo $query;
        echo self::getDb()->error;
        return false;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                verhuur WHERE id = '$id' ";

        return self::getDb()->query($query);
    }
}