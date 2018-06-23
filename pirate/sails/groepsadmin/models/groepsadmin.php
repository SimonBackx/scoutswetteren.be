<?php
namespace Pirate\Model\Groepsadmin;
use Pirate\Model\Model;
use Pirate\Curl\Curl;
use Pirate\Curl\Method;
use Pirate\Curl\DataType;
use Pirate\Model\Leden\Lid;

class Groepsadmin {
    private $access_token = '';
    public $logged_in = false;
    public $ledenlijst = null;

    // Todo: voeg dit toe aan de database configuratie!
    private $username = "simonb";
    private $password = "o2209g";
    private $groepsNummer = "O2209G";

    function __construct() {
    }

    private function authenticatedRequest($method, $url, $headers = [], $data_type = DataType::urlencoded, $data = null) {
        $headers[] = 'Authorization: Bearer '.$this->access_token;
        return Curl::request($method, $url, $headers, $data_type, $data);
    }

    function login() {
        $response = Curl::request(Method::POST, 'https://login.scoutsengidsenvlaanderen.be/auth/realms/scouts/protocol/openid-connect/token', [], DataType::urlencoded, [
            'client_id' => 'groepsadmin-production-client',
            'username' => $this->username,
            'password' => $this->password,
            'grant_type' => 'password',
        ]);
        
        if (!isset($response)) {
            return false;
        }

        if (isset($response["access_token"])) {
            $this->access_token = $response["access_token"];
            $this->logged_in = true;
            return true;
        }

        return false;
    }

    private function filterHuidigeLeden() {
        $columns = GroepsadminLid::getColumns();
        $response = static::authenticatedRequest(Method::PATCH, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst/filter/huidige', [], DataType::json, [
            'criteria' => [
                'groepen' => [$this->groepsNummer],
                'functies' => ["d5f75b320b812440010b812554790354","d5f75b320b812440010b812555de03a2","d5f75b320b812440010b8125567703cb","d5f75b320b812440010b812555db03a1","d5f75b320b812440010b812555d603a0","d5f75b320b812440010b812555c7039d","d5f75b320b812440010b8125565203c1","d5f75b320b812440010b812555380380","d5f75b320b812440010b812555c1039b"],
                'oudleden' => false,
            ],
            'kolommen' => $columns,
            'sortering' => [$columns[0]],
            "type" => "lid",
            "groep" => $this->groepsNummer,
        ]);

        return isset($response);
    }

    private function filterOudLeden() {
        $columns = GroepsadminLid::getColumns();
        $response = static::authenticatedRequest(Method::PATCH, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst/filter/huidige', [], DataType::json, [
            'criteria' => [
                'groepen' => [$this->groepsNummer],
                'functies' => ["d5f75b320b812440010b812554790354","d5f75b320b812440010b812555de03a2","d5f75b320b812440010b8125567703cb","d5f75b320b812440010b812555db03a1","d5f75b320b812440010b812555d603a0","d5f75b320b812440010b812555c7039d","d5f75b320b812440010b8125565203c1","d5f75b320b812440010b812555380380","d5f75b320b812440010b812555c1039b"],
                'oudleden' => true,
            ],
            'kolommen' => $columns,
            'sortering' => [$columns[0]],
            "type" => "lid",
            "groep" => $this->groepsNummer,
        ]);

        return isset($response);
    }

    // Return false on fail, array on success
    private function downloadLedenlijst($offset = 0) {
        $response = static::authenticatedRequest(Method::GET, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst?aantal=100&offset='.urlencode($offset));
        if (!isset($response)) {
            return null;
        }

        if (isset($response["aantal"])) {
            $aantal = $response["aantal"]; // aantal teruggegeven leden
            $totaal = $response["totaal"]; // totaal aantal leden

            // lees leden in
            
            $leden = array();
            foreach ($response['leden'] as $lid_data) {
                $lid = new GroepsadminLid($lid_data);
                $leden[] = $lid;
            }

            if ($totaal > count($leden)+$offset) {
                $extra = $this->downloadLedenlijst(count($leden) + $offset);
                if (!isset($extra)) {
                    return null;
                }
                $leden = array_merge($leden, $extra);
            }
            
            return $leden;
        }

        return null;
    }

    function getLedenlijst() {
        if (!$this->logged_in) {
            return false;
        }
        if (!$this->filterHuidigeLeden()) {
            return false;
        }

        $this->ledenlijst = $this->downloadLedenlijst();
        if (isset($this->ledenlijst)) {
            return true;
        }

        $this->ledenlijst = null;
        return false;
    }

    function getOudLedenlijst() {
        if (!$this->logged_in) {
            return false;
        }
        if (!$this->filterOudLeden()) {
            return false;
        }

        $this->ledenlijst = $this->downloadLedenlijst();
        if (isset($this->ledenlijst)) {
            return true;
        }

        $this->ledenlijst = null;
        return false;
    }
}

class GroepsadminLid {
    public $id;
    public $voornaam;
    public $achternaam;
    public $geboortedatum;
    public $lidnummer;
    public $hash;

    public $found = false;

    function __construct($data) {
        $this->id = $data['id'];
        $waarden = $data['waarden'];

        // lees belangrijkste data in
        $this->achternaam = $waarden['be.vvksm.groepsadmin.model.column.AchternaamColumn'];
        $this->voornaam = $waarden['be.vvksm.groepsadmin.model.column.VoornaamColumn'];
        $this->geboortedatum = $waarden['be.vvksm.groepsadmin.model.column.GeboorteDatumColumn']; // DD/MM/YYYY
        $this->lidnummer = $waarden['be.vvksm.groepsadmin.model.column.LidNummerColumn'];
        $this->hash = $waarden['39a96d046403c4b10164248c1f2e071a'];
    }

    function isEqual(Lid $lid) {
        if (!empty($lid->lidnummer) && $lid->lidnummer != $this->lidnummer) {
            // Lid werd wel al gesynct en verschilt
            return false;
        }

        if (!empty($lid->lidnummer) && $lid->lidnummer == $this->lidnummer) {
            return true;
        }

        if ($this->hash == "") {
            // Dit is een lid dat nog nooit werd gesynchroinseerd.
            // Extra voorzichtig zijn
            $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');
            if (
                trim(clean_special_chars($lid->voornaam)) == trim(clean_special_chars($this->voornaam))
                && trim(clean_special_chars($lid->achternaam)) == trim(clean_special_chars($this->achternaam))
                && $geboortedatum_string == $this->geboortedatum
            ) {
                // Met zekerheid gevonden
                return true;
            }
        }

        return false;
    }

    // Returnt true als ze waarschijnlijk gelijk zijn, maar niet met zekerheid
    // Voer dit enkel uit als er geen andere matches gevonden werden
    function isProbablyEqual(Lid $lid) {
        $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');
        $count = 0;
        if (trim(clean_special_chars($lid->voornaam)) == trim(clean_special_chars($this->voornaam))) {
            $count++;
        }

        if (trim(clean_special_chars($lid->achternaam)) == trim(clean_special_chars($this->achternaam))) {
            $count++;
        }

        if ($geboortedatum_string == $this->geboortedatum) {
            $count++;
        }

        if ($count >= 2) {
            return true;
        }

        return false;
    }

    function markFound($lid) {
        if ($lid->lidnummer != $this->lidnummer) {
            $lid->lidnummer = $this->lidnummer;
            $lid->save();
        }
        $this->found = true;
    }

    function needsSync($lid) {
        // Als de groepsadmin hash leeg is
        if (empty($this->hash)) {
            return true;
        }
        return false;
    }

    // All data we 
    static function getColumns() {
        return [
            'be.vvksm.groepsadmin.model.column.LidNummerColumn',
            'be.vvksm.groepsadmin.model.column.VoornaamColumn',
            'be.vvksm.groepsadmin.model.column.AchternaamColumn',
            'be.vvksm.groepsadmin.model.column.GeboorteDatumColumn',  // DD/MM/YYYY
            "be.vvksm.groepsadmin.model.column.GeslachtColumn",

            // Groepseigen (Hash)
            "39a96d046403c4b10164248c1f2e071a",
        ];

        /*
        "be.vvksm.groepsadmin.model.column.LidNummerColumn",
        "be.vvksm.groepsadmin.model.column.VoornaamColumn",
        "be.vvksm.groepsadmin.model.column.AchternaamColumn",
        "be.vvksm.groepsadmin.model.column.AdresColumn",
        "be.vvksm.groepsadmin.model.column.EmailColumn",
        "be.vvksm.groepsadmin.model.column.ContactEmailColumn",
        "be.vvksm.groepsadmin.model.column.Contact2GsmColumn",
        "be.vvksm.groepsadmin.model.column.VolledigeNaamColumn",
        "be.vvksm.groepsadmin.model.column.GsmColumn",
        "be.vvksm.groepsadmin.model.column.LeeftijdColumn",
        "be.vvksm.groepsadmin.model.column.GeboorteDatumColumn",
        "be.vvksm.groepsadmin.model.column.GeslachtColumn",
        "be.vvksm.groepsadmin.model.column.RekeningnummerColumn",
        "be.vvksm.groepsadmin.model.column.StraatnaamColumn",
        "be.vvksm.groepsadmin.model.column.StraatColumn",
        "be.vvksm.groepsadmin.model.column.StraatnummerColumn",
        "be.vvksm.groepsadmin.model.column.BusColumn",
        "be.vvksm.groepsadmin.model.column.GemeenteColumn",
        "be.vvksm.groepsadmin.model.column.GemeentenaamColumn",
        "be.vvksm.groepsadmin.model.column.PostcodeColumn",
        "be.vvksm.groepsadmin.model.column.TelefoonColumn",
        "be.vvksm.groepsadmin.model.column.PerAdresColumn",
        "be.vvksm.groepsadmin.model.column.ContactGsmColumn",
        "be.vvksm.groepsadmin.model.column.ContactNaamColumn",
        "be.vvksm.groepsadmin.model.column.Contact2NaamColumn",
        "be.vvksm.groepsadmin.model.column.Contact2EmailColumn"
    ],
        */
    }
}