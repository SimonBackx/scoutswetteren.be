<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Afrekening;
use Pirate\Model\Leden\Inschrijving;

class Inschrijving extends Model {
    public $id;
    public $lid; // Object
    public $datum;
    public $scoutsjaar;
    public $tak;
    
    //public $betaald_cash;

    public $prijs;
    public $afrekening; // id
    public $afrekening_oke;
    public $halfjaarlijks;

    public static $lidgeld_per_tak = array('kapoenen' => 40, 'wouters' => 40, 'jonggivers' => 40, 'givers' => 40, 'jin' => 40);
    public static $lidgeld_per_tak_halfjaar = array('kapoenen' => 20, 'wouters' => 20, 'jonggivers' => 20, 'givers' => 20, 'jin' => 20);

    public static $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin');

    public static $inschrijvings_start_maand = 9;
    public static $inschrijvings_einde_maand = 7;
    public static $inschrijvings_halfjaar_maand = 3; // Vanaf maart halfjaarlijks lidgeld

    static private $scoutsjaar_cache = null; // cache

    function __construct($row = array(), $lid_object = null) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['inschrijving_id'];

        if (is_null($lid_object)) {
            $this->lid = new Lid($row, $this);
        } else {
            $this->lid = $lid_object;
        }
        
        $this->datum = new \DateTime($row['datum']);
        $this->scoutsjaar = $row['scoutsjaar'];
        $this->tak = $row['tak'];
        
        //$this->betaald_cash = $row['inschrijving_betaald_cash'];

        $this->afrekening = $row['afrekening'];
        $this->afrekening_oke = ($row['afrekening_oke'] == 1);

        $this->prijs = $row['prijs'];
        $this->halfjaarlijks = $row['halfjaarlijks'];
    }

    public static function isGeldigeTak($tak) {
        return in_array($tak, Self::$takken);
    }

    function getVerbondTak() {
        $mapping = [
            "kapoenen" => [
                "naam" => "Kapoenen",
                "functie" => "d5f75b320b812440010b812555de03a2",
            ],
            "wouters" => [
                "naam" => "Kabouters",
                "functie" => "d5f75b320b812440010b812555db03a1",
            ],
            "jonggivers" => [
                "naam" => "Jong gidsen",
                "functie" => "d5f75b320b812440010b812555c7039d",
            ],
            "givers" => [
                "naam" => "Gidsen",
                "functie" => "d5f75b320b812440010b812555380380",
            ],
            "jin" => [
                "naam" => "Jin",
                "functie" => "d5f75b320b812440010b812555c1039b",
            ],
        ];

        if ($this->lid->geslacht == "M") {
            $mapping = [
                "kapoenen" => [
                    "naam" => "Kapoenen",
                    "functie" => "d5f75b320b812440010b812555de03a2",
                ],
                "wouters" => [
                    "naam" => "Welpen",
                    "functie" => "d5f75b320b812440010b8125567703cb",
                ],
                "jonggivers" => [
                    "naam" => "Jong verkenners",
                    "functie" => "d5f75b320b812440010b812555d603a0",
                ],
                "givers" => [
                    "naam" => "Verkenners",
                    "functie" => "d5f75b320b812440010b8125565203c1",
                ],
                "jin" => [
                    "naam" => "Jin",
                    "functie" => "d5f75b320b812440010b812555c1039b",
                ],
            ];
        }

        if (!isset($mapping[$this->tak])) {
            return null;
        }
        return $mapping[$this->tak];
    }

    function isBetaald() {
        return $this->afrekening_oke;
    }

    function getPrijs() {
        return '€ '.money_format('%!.2n', $this->prijs);
    }

    function getTakJaar() {
        $verdeling = Lid::getTakkenVerdeling($this->scoutsjaar);
        $jaar = intval($this->lid->geboortedatum->format('Y'));
        $min = 0;

        foreach ($verdeling as $geboortejaar => $tak) {
            if ($tak == $this->tak) {
                if ($geboortejaar > $min) {
                    $min = $geboortejaar;
                }
            }
        }

        return $min - $jaar + 1;

    }

    // Inschrijvingen vanaf juli verbieden
    static function isInschrijvingsPeriode() {
        $maand = intval(date('n'));
        if ($maand < self::$inschrijvings_start_maand && $maand > self::$inschrijvings_einde_maand) {
            return false;
        }
        return true;
    }

    static function isHalfjaarlijksLidgeld() {
        $maand = intval(date('n'));
        if (self::$inschrijvings_halfjaar_maand >= self::$inschrijvings_start_maand) {
            // Halfjaar ligt nog voor januari
            if ($maand >= self::$inschrijvings_halfjaar_maand) {
                return true;
            }
        }
        elseif ($maand >= self::$inschrijvings_halfjaar_maand && $maand <= self::$inschrijvings_einde_maand) {
            return true;
        }
        return false;
    }

    static function getScoutsjaarFor($year, $month) {
        if ($month >= self::$inschrijvings_start_maand) {
            return $year;
        } else {
            return $year - 1;
        }
    }

    static function getScoutsjaar($year = null) {
        if (is_null(self::$scoutsjaar_cache)) {
            $jaar = intval(date('Y'));
            $maand = intval(date('n'));
            if ($maand >= self::$inschrijvings_start_maand) {
                self::$scoutsjaar_cache = $jaar;
            } else {
                self::$scoutsjaar_cache = $jaar - 1;
            }
        }
        return self::$scoutsjaar_cache;
    }


    // UPDATE inschrijvingen set halfjaarlijks = 0

    static function schrijfIn(Lid $lid) {
        $scoutsjaar = self::getScoutsjaar();

        $jaar = self::getDb()->escape_string($scoutsjaar);

        $tak = Lid::getTak(intval($lid->geboortedatum->format('Y')), Lid::areLimitsIgnored());
        if ($tak === false) {
            return false;
        }
        
        $tak = self::getDb()->escape_string($tak);

        $halfjaarlijks = self::isHalfjaarlijksLidgeld();
        $lidgeld = self::getLidgeld($tak, $halfjaarlijks);

        $halfjaarlijks_string = '0';
        if ($halfjaarlijks) {
            $halfjaarlijks_string = '1';
        }

        $prijs = self::getDb()->escape_string($lidgeld);

        $inschrijving = new Inschrijving();

        $lid_id = self::getDb()->escape_string($lid->id);

        $query = "INSERT INTO 
                inschrijvingen (`lid`,  `scoutsjaar`, `tak`, `prijs`, `halfjaarlijks`)
                VALUES ('$lid_id', '$jaar', '$tak', '$prijs', '$halfjaarlijks_string')";

        if (self::getDb()->query($query)) {
            $inschrijving->id = self::getDb()->insert_id;
            $inschrijving->lid = $lid;
            $inschrijving->datum = new \DateTime();
            $inschrijving->scoutsjaar = $scoutsjaar;
            $inschrijving->tak = $tak;
            $inschrijving->halfjaarlijks = $halfjaarlijks;

            $inschrijving->prijs = $lidgeld;

            $lid->inschrijving = $inschrijving;
            return true;
        }
        return false;
    }

    private static function getLidgeld($tak, $halfjaarlijks = false) {
        $tak = strtolower($tak);

        if (!isset(self::$lidgeld_per_tak[$tak])) {
            // Fatale fout!
            return 0;
        }

        if ($halfjaarlijks) {
            if (!isset(self::$lidgeld_per_tak_halfjaar[$tak])) {
                // Fatale fout!
                return 0;
            }
            return self::$lidgeld_per_tak_halfjaar[$tak];
        }
        
        return self::$lidgeld_per_tak[$tak];
    }

    function save() {
        if (!isset($this->id)) {
            return false;
        }

        $id = self::getDb()->escape_string($this->id);
        $tak = self::getDb()->escape_string($this->tak);

        $query = "UPDATE inschrijvingen 
                SET 
                 `tak` = '$tak'
                 where `inschrijving_id` = '$id'
            ";

        if (!self::getDb()->query($query)) {
            return false;
        }

       return true;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                inschrijvingen WHERE inschrijving_id = '$id' ";

        // todo: afrekening aanpassen (prijs)
        
        // todo: lid verwijderen indien enigste inschrijving van dit lid

        if (self::getDb()->query($query)) {
            $afrekening = Afrekening::getAfrekening($this->afrekening);
            if (isset($afrekening)) {
                $afrekening->recalculate();
            }

            // lid opnieuw ophalen
            $lid = Lid::getLid($this->lid->id);

            if (empty($lid->inschrijving)) {
                // enigste inschrijving is weg -> verwijderen
                $lid->delete();
            }

            return true;
        }
        return false;
    }
}