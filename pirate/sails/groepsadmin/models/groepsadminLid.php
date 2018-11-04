<?php
namespace Pirate\Model\Groepsadmin;
use Pirate\Model\Model;
use Pirate\Curl\Curl;
use Pirate\Curl\Method;
use Pirate\Curl\DataType;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Groepsadmin\Groepsadmin;

class GroepsadminLid {
    public $id;
    public $voornaam;
    public $achternaam;
    public $geboortedatum;
    public $lidnummer;
    public $hash;

    private $linkedLid = null;

    public $found = false;

    function __construct($data = null) {
        if (!isset($data)) {
            return;
        }
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

        // Extra voorzichtig zijn
        $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');
        if (
            trim(clean_special_chars($lid->voornaam)) == trim(clean_special_chars($this->voornaam))
            && trim(clean_special_chars($lid->achternaam)) == trim(clean_special_chars($this->achternaam))
            && $geboortedatum_string == trim($this->geboortedatum)
        ) {
            // Met zekerheid gevonden
            return true;
        }

        if (!empty($this->hash) && static::calculateHash($lid) == $this->hash) {
            return true;
        }

        return false;
    }

    // Returnt true als ze waarschijnlijk gelijk zijn, maar niet met zekerheid
    // Voer dit enkel uit als er geen andere matches gevonden werden
    // Bedoeling is dat er bij mogelijke equals enkel manuele interactie is om veiligheidsproblemen te voorkomen
    function isProbablyEqual(Lid $lid) {
        $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');
        $count = 0;
        if (trim(clean_special_chars($lid->voornaam)) == trim(clean_special_chars($this->voornaam))) {
            $count++;
        }

        if (trim(clean_special_chars($lid->achternaam)) == trim(clean_special_chars($this->achternaam))) {
            $count++;
        }

        if ($geboortedatum_string == trim($this->geboortedatum)) {
            $count++;
        }

        if ($count >= 2) {
            return true;
        }

        return false;
    }

    function markFound($lid) {
        if ((empty($lid->lidnummer) || $lid->lidnummer != $this->lidnummer) && !empty($this->lidnummer)) {
            $lid->lidnummer = $this->lidnummer;
            $lid->save();
        }
        $this->found = true;
        $this->linkedLid = $lid;
    }

    function needsSync() {
        // Als de groepsadmin hash leeg is
        if (empty($this->hash) || (empty($this->linkedLid->lidnummer) && !empty($this->lidnummer))) {
            return true;
        }

        // Todo: misschien ook gewoon syncen als hash gelijk is, maar de velden toch aangepast zijn (= aanpassing in groepsadministratie :o)

        return $this->hash != $this->calculateHash($this->linkedLid);
    }

    function remove($groepsadmin) {
        
        // Stap 1: huidige data ophalen van de groepsadmin
        $fetchedData = $groepsadmin->downloadLid($this->id);


        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Downloaden van lid gefaald", "Het downloaden van het lid is gefaald (bij remove)", "id = $this->id");
            return false;
        }

        $log = 'Lid opgehaald: '.json_encode($fetchedData, JSON_PRETTY_PRINT)."\n\n";

        // Stap 2: Alle functies beïndigen
        $functies = [];
        foreach ($fetchedData['functies'] as $functie) {
            $functie['einde'] = date('Y-m-d').'T'.date('H:i:s').'.000+02:00';
            unset($functie['links']);
            $functies[] = $functie;
        }

        $newData = [
            'functies' => $functies,
        ];

        $log .= 'Lid data dat zal worden doorgestuurd (voor schrappen): '.json_encode($newData, JSON_PRETTY_PRINT)."\n\n";
        
        $fetchedData = $groepsadmin->uploadLid($newData, $this->id);
        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Schrappen van lid is gefaald", "Het schrappen van het lid is gefaald", $log);
            return false;
        }

        return true;
    }

    function sync($groepsadmin) {
        // Niet toegestaan atm
        if (!isset($this->linkedLid)) {
            return false;
        }

        // Stap 1: huidige data ophalen van de groepsadmin
        $fetchedData = $groepsadmin->downloadLid($this->id);


        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Downloaden van lid gefaald", "Het downloaden van het lid is gefaald", "id = $this->id");
            return false;
        }

        $log = 'Lid opgehaald: '.json_encode($fetchedData, JSON_PRETTY_PRINT)."\n\n";

        // Stap 2: data versturen, maar contacten weglaten als niet alle adresId velden gegeven zijn
        $newData = static::getDataFor($this->linkedLid, $fetchedData);

        
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

        $log .= 'Lid data dat zal worden doorgestuurd: '.json_encode($newData, JSON_PRETTY_PRINT)."\n\n";
        
        $fetchedData = $groepsadmin->uploadLid($newData, $this->id);
        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Aanpassen van lid gefaald", "Het aanpassen van het lid is gefaald", $log);
            return false;
        }

        $log .= 'Lid na aanpassing: '.json_encode($fetchedData, JSON_PRETTY_PRINT)."\n\n";
        
        if (!$adressenOk) {
            // Stap 3: als contacten weggelaten werden => data opnieuw berekenen met returnwaarde van vorige stap
            // en nu nog eens opslaan
            $newData = static::getDataFor($this->linkedLid, $fetchedData);
            $log .= 'Lid data dat zal worden doorgestuurd (met contacten): '.json_encode($newData, JSON_PRETTY_PRINT)."\n\n";

            $fetchedData = $groepsadmin->uploadLid($newData, $this->id);

            if (!isset($fetchedData)) {
                Leiding::sendErrorMail("Aanpassen van lid contacten gefaald", "Het aanpassen van lid contacten is gefaald", $log);
                return false;
            }
        }

        return true;
    }

    static function createNew($lid, $groepsadmin) {
        // Stap 1: lid aanmaken, maar contacten weglaten
        $newData = static::getDataFor($lid);
        unset($newData['contacten']);

        $log = 'Create lid (zonder contacten): '.json_encode($newData, JSON_PRETTY_PRINT)."\n\n";

        $fetchedData = $groepsadmin->uploadLid($newData);
        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Lid aanmaken is gefaald", "Het aanmaken van een lid is gefaald", $log);
            return false;
        }

        $log .= 'Antwoord: '.json_encode($fetchedData, JSON_PRETTY_PRINT)."\n\n";

        // Stap 2: contacten nu ook sturen (nu we alle adressen hebben en id's kunnen correleren)
        $newData = static::getDataFor($lid, $fetchedData);
        $log .= 'Contacten nu ook versturen: '.json_encode($newData, JSON_PRETTY_PRINT)."\n\n";

        $fetchedData = $groepsadmin->uploadLid($newData, $fetchedData['id']);
        if (!isset($fetchedData)) {
            Leiding::sendErrorMail("Lid aanmaken is gefaald", "Het aanmaken van een lid is gefaald (contacten toevoegen mislukt)", $log);
            return false;
        }

        // Successvol aangemaakt
        return true;
    } 

    static function getDataFor($lid, $fetchedData = null) {
        // Use fetchedData to corelate Id's
        // Zonder fetchedData wordt deze functie gebruikt om een hash te genereren én om een nieuw lid aan te maken
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

        // Als er al adressen zijn in fetchedDAta, is het heel belangrijk dat we één daarvan gebruiken als postadres
        // Anders gooit groepsadministratie een 500 error :(
        $postadresIngesteld = false;

        foreach ($lid->ouders as $ouder) {
            if (isset($addedAdressen[$ouder->adres->id])) {
                continue;
            }
            $addedAdressen[$ouder->adres->id] = true;
            $adres = [
                // "id" => null, => niet meesturen als het niet gekend is!
                "land" => "BE",
                "postcode" => $ouder->adres->postcode,
                "gemeente" => $ouder->adres->gemeente,
                "straat" => $ouder->adres->straatnaam,
                "giscode" => $ouder->adres->giscode,
                "nummer" => $ouder->adres->huisnummer,
                "bus" => isset($ouder->adres->busnummer) ? $ouder->adres->busnummer : "",
                "telefoon" => str_replace(' ', ' ', $ouder->adres->telefoon),
                "postadres" => false, // Er mag maar 1 postadres zijn!
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

                        if ($a['postadres'] == true && !$postadresIngesteld) {
                            // Postadres behouden heeft de voorkeur
                            $adres['postadres'] = true;
                            $postadresIngesteld = true;
                        }
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

        if (!$postadresIngesteld && isset($data["adressen"][0])) {
            $data["adressen"][0]['postadres'] = true;
            $postadresIngesteld = true;
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

        // Functies
        // Niet nodig om elke keer opnieuw te sturen (enkel degene die je wilt verwijderen of toevoegen)
        $functie = $lid->inschrijving->getVerbondTak()['functie'];
        if (isset($fetchedData)) {
            // Staat de gezochte tak nog in de lijst?
            
            $found = false;
            foreach ($fetchedData['functies'] as $f) {
                if ($f['functie'] == $functie && empty($f['einde'])) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Tak terug toevoegen
                $data['functies'][] = [
                    "groep" => "O2209G",
                    "functie" => $functie,
                    "begin" => $lid->inschrijving->datum->format("Y-m-d"),
                    "einde" => null,
                ];
            } else {
                // Functie staat nog in de groepsadministratie
            }

            // Nu alle andere functies wissen (we staan dit niet toe bij gewone leden)
            foreach ($fetchedData['functies'] as $f) {
                if ($f['functie'] != $functie) {
                    $f['einde'] = date('Y-m-d').'T'.date('H:i:s').'.000+02:00';
                    unset($f['links']);
                    $data['functies'][] = $f;
                }
            }
        } else {
            // We willen enkel de huidige functie toevoegen (= nieuwe leden)
            $data['functies'][] = [
                "groep" => "O2209G",
                "functie" => $functie,
                "begin" => $lid->inschrijving->datum->format("Y-m-d"),
                "einde" => null,
            ];
        }
        

        /*"functies": [
            {
              "groep": "A3143G",
              "functie": "d5f75e23385c5e6e0139493b8546035e",
              "begin": "2014-01-01",
              "einde": "2014-03-02"
              "links": [
                {
                  "href": "/groepsadmin/rest-ga/groep/A3143G",
                  "rel": "groep",
                  "method": "GET"
                }, {
                  "href": "/groepsadmin/rest-ga/functie/d5f75e23385c5e6e0139493b8546035e",
                  "rel": "functie",
                  "method": "GET"
                }
              ]
            }
        ],*/

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