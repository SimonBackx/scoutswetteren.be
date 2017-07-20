<?php
namespace Pirate\Model\Groepsadmin;
use Pirate\Model\Model;

class Groepsadmin {
    private $access_token = '';
    public $logged_in = false;
    public $ledenlijst = null;

    function __construct() {
    }

    function Login() {
        try {
            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://login.scoutsengidsenvlaanderen.be/auth/realms/scouts/protocol/openid-connect/token',
                CURLOPT_USERAGENT => 'cURL Request',
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => 'client_id=groepsadmin-production-client&username=simonb&password=o2209g&grant_type=password',
                CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
            ));

            $result = curl_exec($curl);
            
            if (!$result) {
                return false;
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($status == 200) {
                // decode json
                $data = @json_decode($result, true);
                if (isset($data["access_token"])) {
                    $this->access_token = $data["access_token"];
                    $this->logged_in = true;
                    return true;
                }
            }

            return false;
        }
        catch (Exception $e) {
            return false;
        }
    }

    function SetFilter() {
        $content = '{"criteria":{"groepen":[],"functies":["d5f75b320b812440010b812554790354","d5f75b320b812440010b812555de03a2","d5f75b320b812440010b8125567703cb","d5f75b320b812440010b812555db03a1","d5f75b320b812440010b812555d603a0","d5f75b320b812440010b812555c7039d","d5f75b320b812440010b8125565203c1","d5f75b320b812440010b812555380380","d5f75b320b812440010b812555c1039b"],"oudleden":false},"kolommen":["be.vvksm.groepsadmin.model.column.LidNummerColumn","be.vvksm.groepsadmin.model.column.VoornaamColumn","be.vvksm.groepsadmin.model.column.AchternaamColumn","be.vvksm.groepsadmin.model.column.GeboorteDatumColumn","be.vvksm.groepsadmin.model.column.VVKSMFunktiesColumn"],"groepen":[],"sortering":[],"type":"groep","links":[{"rel":"self","href":"http://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst/filter/39a96d045785391a015787429ac35753","method":"GET","secties":[]}]}';
        
        try {            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst/filter/huidige',
                CURLOPT_HTTPHEADER => array('Content-Type: application/json;charset=UTF-8', 'Authorization: Bearer '.$this->access_token),
                CURLOPT_POSTFIELDS => $content,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
            ));

            $result = curl_exec($curl);
            
            if (!$result) {
                return false;
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($status == 200) {
               return true;
            }

            return false;
        }
        catch (Exception $e) {
            return false;
        }
    }

    // Return false on fail, array on success
    function DownloadLedenlijst($offset = 0) {
        // todo: filter goed instellen zodat juiste velden enzo worden weergegeven!
        
        try {            
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/ledenlijst?aantal=100&offset='.$offset,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: Bearer '.$this->access_token)
            ));

            $result = curl_exec($curl);
            
            if (!$result) {
                return false;
            }

            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($status == 200) {
                // decode json
                $data = @json_decode($result, true);
                if (isset($data["aantal"])) {
                    $aantal = $data["aantal"]; // aantal teruggegeven leden
                    $totaal = $data["totaal"]; // totaal aantal leden

                    // lees leden in
                    
                    $leden = array();
                    foreach ($data['leden'] as $lid_data) {
                        $lid = new GroepsadminLid($lid_data);
                        $leden[] = $lid;
                    }

                    if ($totaal > count($leden)+$offset) {
                        $extra = $this->DownloadLedenlijst(count($leden) + $offset);
                        if (!$extra) {
                            return false;
                        }
                        $leden = array_merge($leden, $extra);
                    }
                    
                    return $leden;
                }
            }

            return false;
        }
        catch (Exception $e) {
            return false;
        }

        // return false on 
    }

    function GetLedenlijst() {
        if (!$this->logged_in) {
            return false;
        }
        if (!$this->SetFilter()) {
            return false;
        }

        $this->ledenlijst = $this->DownloadLedenlijst();
        if ($this->ledenlijst) {
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