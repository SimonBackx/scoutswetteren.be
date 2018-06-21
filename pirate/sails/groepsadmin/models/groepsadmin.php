<?php
namespace Pirate\Model\Groepsadmin;
use Pirate\Model\Model;
use Pirate\Curl\Curl;
use Pirate\Curl\Method;
use Pirate\Curl\DataType;

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

    private function setFilter() {
        $response = static::authenticatedRequest(Method::PATCH, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst/filter/huidige', [], DataType::json, [
            'criteria' => [
                'groepen' => [$this->groepsNummer],
                'functies' => ["d5f75b320b812440010b812554790354","d5f75b320b812440010b812555de03a2","d5f75b320b812440010b8125567703cb","d5f75b320b812440010b812555db03a1","d5f75b320b812440010b812555d603a0","d5f75b320b812440010b812555c7039d","d5f75b320b812440010b8125565203c1","d5f75b320b812440010b812555380380","d5f75b320b812440010b812555c1039b"],
                'oudleden' => false,
            ],
            'kolommen' => ["be.vvksm.groepsadmin.model.column.LidNummerColumn","be.vvksm.groepsadmin.model.column.VoornaamColumn","be.vvksm.groepsadmin.model.column.AchternaamColumn","be.vvksm.groepsadmin.model.column.GeboorteDatumColumn","be.vvksm.groepsadmin.model.column.VVKSMFunktiesColumn"],
            'sortering' => ["be.vvksm.groepsadmin.model.column.LidNummerColumn"],
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
            echo "Login failed";
            return false;
        }
        if (!$this->setFilter()) {
            echo "Filter failed";
            return false;
        }

        $this->ledenlijst = $this->downloadLedenlijst();
        if (isset($this->ledenlijst)) {
            return true;
        }

        echo "downloadLedenlijst failed";
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

    function __construct($data) {
        $this->id = $data['id'];
        $waarden = $data['waarden'];

        // lees belangrijkste data in
        $this->achternaam = $waarden['be.vvksm.groepsadmin.model.column.AchternaamColumn'];
        $this->voornaam = $waarden['be.vvksm.groepsadmin.model.column.VoornaamColumn'];
        $this->geboortedatum = $waarden['be.vvksm.groepsadmin.model.column.GeboorteDatumColumn']; // DD/MM/YYYY
        $this->lidnummer = $waarden['be.vvksm.groepsadmin.model.column.LidNummerColumn'];

        //VVKSMFunktiesColumn
    }
}