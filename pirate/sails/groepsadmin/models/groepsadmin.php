<?php
namespace Pirate\Model\Groepsadmin;
use Pirate\Model\Model;
use Pirate\Curl\Curl;
use Pirate\Curl\Method;
use Pirate\Curl\DataType;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;

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

    function authenticatedRequest($method, $url, $headers = [], $data_type = DataType::urlencoded, $data = null) {
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

    function downloadLid($id) {
        return static::authenticatedRequest(Method::GET, 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/lid/'.$id);
    }

    function uploadLid($data, $id = null) {
        $response = static::authenticatedRequest(isset($id) ? Method::PATCH : Method::POST, isset($id) ? 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/lid/'.$id.'?bevestig=true' : 'https://groepsadmin.scoutsengidsenvlaanderen.be/groepsadmin/rest-ga/lid', [], DataType::json, $data);
        return $response;
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

    private $linkedLid = null;

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

        $this->linkedLid = null;
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
        $this->linkedLid = $lid;
    }

    function needsSync() {
        // Als de groepsadmin hash leeg is
        if (empty($this->hash)) {
            return true;
        }

        return $this->hash != $this->calculateHash($this->linkedLid);
    }

    function sync($groepsadmin) {
        if (!isset($this->linkedLid)) {
            return false;
        }

        // Stap 1: huidige data ophalen van de groepsadmin
        $fetchedData = $groepsadmin->downloadLid($this->id);
        echo '<pre>'.json_encode($fetchedData, JSON_PRETTY_PRINT).'</pre>';

        if (!isset($fetchedData)) {
            exit;
            return false;
        }

        // Stap 2: data versturen, maar contacten weglaten als niet alle adresId velden gegeven zijn
        $newData = static::getDataFor($this->linkedLid, $fetchedData);

        echo '<pre>'.json_encode($newData, JSON_PRETTY_PRINT).'</pre>';
        
        $adressenOk = true;
        foreach ($newData['contacten'] as $contact) {
            if (!isset($contact['adres'])) {
                $adressenOk = false;
                break;
            }
        }

        if (!$adressenOk) {
            unset($newData['contacten']);
        }

        echo '<pre>'.json_encode($newData, JSON_PRETTY_PRINT).'</pre>';
        
        $fetchedData = $groepsadmin->uploadLid($newData, $this->id);
        if (!isset($fetchedData)) {
            exit;
            return false;
        }

        echo '<pre>'.json_encode($fetchedData, JSON_PRETTY_PRINT).'</pre>';

        if (!$adressenOk) {
            // Stap 3: als contacten weggelaten werden => data opnieuw berekenen met returnwaarde van vorige stap
            // en nu nog eens opslaan
            $newData = static::getDataFor($this->linkedLid, $fetchedData);
            echo '<pre>'.json_encode($newData, JSON_PRETTY_PRINT).'</pre>';

            $fetchedData = $groepsadmin->uploadLid($newData, $this->id);

            echo '<pre>'.json_encode($fetchedData, JSON_PRETTY_PRINT).'</pre>';
            if (!isset($fetchedData)) {
                exit;
                return false;
            }
        }

        exit;

        return true;
    }

    static function getDataFor($lid, $fetchedData = null) {
        // Use fetchedData to corelate Id's
        $data = [
            "persoonsgegevens" => [
                "geslacht" => ($lid->geslacht == 'M' ? 'man' : 'vrouw'),
                "gsm" => isset($lid->gsm) ? str_replace(' ', ' ', $lid->gsm) : "",
               
                //"rekeningnummer" => "BE68 5390 0754 7034"
            ],
            "email" => $lid->ouders[0]->email,
            "vgagegevens" => [
                "voornaam" => $lid->voornaam,
                "achternaam" => $lid->achternaam,
                "geboortedatum" => $lid->geboortedatum->format("Y-m-d"),
                "beperking" => false,
                "verminderdlidgeld" => $lid->gezin->scouting_op_maat,
            ],

            "adressen" => [],

            "contacten" => [],
            
            // Todo: functies
        ];

        // Adressen
        $addedAdressen = [];
        foreach ($lid->ouders as $ouder) {
            if (isset($addedAdressen[$ouder->adres->id])) {
                continue;
            }
            $addedAdressen[$ouder->adres->id] = true;
            $adres = [
                "id" => null,
                "land" => "BE",
                "postcode" => $ouder->adres->postcode,
                "gemeente" => $ouder->adres->gemeente,
                "straat" => $ouder->adres->straatnaam,
                "giscode" => $ouder->adres->giscode,
                "nummer" => $ouder->adres->huisnummer,
                "bus" => isset($ouder->adres->busnummer) ? $ouder->adres->busnummer : "",
                "telefoon" => str_replace(' ', ' ', $ouder->adres->telefoon),
                "postadres" => (count($data["adressen"]) == 0),
                "omschrijving" => "Adres van ".$ouder->titel,
                /*"positie" =>  [
                    "lat" => 51.166969,
                    "lng" => 4.462271
                ]*/
            ];

            if (isset($fetchedData)) {
                $id = null;
                // Adres opzoeken
                foreach($fetchedData["adressen"] as $a) {
                    if ($a['postcode'] == $adres['postcode'] && $a['straat'] == $adres['straat'] && $a['nummer'] == $adres['nummer'] && $a['bus'] == $adres['bus']) {
                        $id = $a['id'];
                        break;
                    }
                }

                if (isset($id)) {
                    $adres['id'] = $id;
                    $addedAdressen[$ouder->adres->id] = $id;
                }
            }

            $data["adressen"][] = $adres;
        }

        // Ouders
        foreach ($lid->ouders as $ouder) {
            $contact = [
                "id" => null,
                "adres" => null,
                "voornaam" => $ouder->voornaam,
                "achternaam" => $ouder->achternaam,
                "rol" => $ouder->getGroepsadminRol(),
                "gsm" => str_replace(' ', ' ', $ouder->gsm),
                "email" => $ouder->email
            ];

            if (isset($fetchedData)) {
                $id = null;
                // Adres opzoeken
                foreach($fetchedData["contacten"] as $c) {
                    if ($c['voornaam'] == $contact['voornaam'] && $c['achternaam'] == $contact['achternaam']) {
                        $id = $c['id'];
                        break;
                    }
                }

                if (isset($id)) {
                    $contact['id'] = $id;
                }
            }

            if (isset($addedAdressen[$ouder->adres->id]) && $addedAdressen[$ouder->adres->id] !== true) {
                $contact['adres'] = $addedAdressen[$ouder->adres->id];
            }
            $data["contacten"][] = $contact;
        }

        // Extra velden
        if (isset($fetchedData)) {
            $data["groepseigenVelden"] = [
                "O2209G" => [
                    "waarden" => [
                        // Hash opslaan hier, enkel als fetchedData != null
                        "39a96d046403c4b10164248c1f2e071a" => static::calculateHash($lid)
                    ]
                ]
            ];

            if (isset($fetchedData['gebruikersnaam'])) {
                // Als het lid een gebruikersnama heeft => VGA mag e-mailadres niet wijzigen
                unset($data['email']);
            }
        }

        return $data;
    }

    static function calculateHash($lid) {
        $data = static::getDataFor($lid);
        $hash = hash('sha256', json_encode($data));
        return $hash;
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