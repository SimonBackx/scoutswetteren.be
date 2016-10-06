<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;

class Inschrijving extends Model {
    public $id;
    public $lid; // Object
    public $datum;
    public $scoutsjaar;
    public $tak;
    public $betaald_cash;
    public $prijs;
    public $afrekening; // id
    public $afrekening_oke;

    public static $lidgeld_per_tak = array('kapoenen' => 42, 'wouters' => 32, 'jonggivers' => 32, 'givers' => 32, 'jin' => 32);
    public static $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin');

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
        $this->betaald_cash = $row['inschrijving_betaald_cash'];

        $this->afrekening = $row['afrekening'];
        $this->afrekening_oke = ($row['afrekening_oke'] == 1);

        $this->prijs = $row['prijs'];
    }

    function isBetaald() {
        // Todo check afrekening
        if ($this->afrekening_oke) {
            return true;
        }

        return $this->betaald_cash >= $this->prijs;
    }


    function getPrijs() {
        return '€ '.money_format('%!.2n', $this->prijs);
    }

    function getTeBetalen() {
        return '€ '.money_format('%!.2n', $this->prijs - $this->betaald_cash);
    }

    function getBetaaldCash() {
        return '€ '.money_format('%!.2n', $this->betaald_cash);
    }

    function getTakJaar() {
        $verdeling = Lid::getTakkenVerdeling(Lid::getScoutsjaar());
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

    // Inschrijvingen vanaf juni verbieden
    static function isInschrijvingsPeriode() {
        $maand = intval(date('n'));
        if ($maand < 9 && $maand >= 6) {
            return false;
        }
        return true;
    }

    static function schrijfIn(Lid $lid) {
        $scoutsjaar = Lid::getScoutsjaar();

        $jaar = self::getDb()->escape_string($scoutsjaar);
        $tak = self::getDb()->escape_string(Lid::getTak(intval($lid->geboortedatum->format('Y'))));

        $lidgeld = self::getLidgeld($tak);
        $prijs = self::getDb()->escape_string($lidgeld);

        $inschrijving = new Inschrijving();

        $lid_id = self::getDb()->escape_string($lid->id);

        $query = "INSERT INTO 
                inschrijvingen (`lid`,  `scoutsjaar`, `tak`, `prijs`)
                VALUES ('$lid_id', '$jaar', '$tak', '$prijs')";

        if (self::getDb()->query($query)) {
            $inschrijving->id = self::getDb()->insert_id;
            $inschrijving->lid = $lid;
            $inschrijving->datum = new \DateTime();
            $inschrijving->scoutsjaar = $scoutsjaar;
            $inschrijving->tak = $tak;

            $inschrijving->prijs = $lidgeld;

            $lid->inschrijving = $inschrijving;
            return true;
        }
        return false;
    }

    private static function getLidgeld($tak) {
        $tak = strtolower($tak);
        if (!isset(self::$lidgeld_per_tak[$tak])) {
            // Fatale fout!
            return 0;
        }
        return self::$lidgeld_per_tak[$tak];
    }

    function betaal(string &$bedrag, &$message, &$errors) {
        $out = 0;
        Validator::validatePrice($bedrag, $out, $errors, true);

        if (count($errors) == 0) {
            // Alle te betalen leden overlopen, en gaten zo goed mogelijk proberen op te vullen

            if ($this->betaald_cash + $out > $this->prijs) {
                $errors[] = 'Er kan niet meer cash betaalt worden dan de inschrijving van dit lid zelf.';
                return false;
            } elseif ($this->betaald_cash + $out < 0){
                $errors[] = 'Je kan niet meer terugbetalen aan een lid dan cash werd betaalt via dat lid. ';
                return false;
            }

            $this->betaald_cash += $out;

            $okay = $this->save();

            if (!$okay) {
                $errors[] = 'Er ging iets mis bij het registreren van de betaling. Controleer of er niet meer betaald wordt dan het gezin nog moet betalen.';
                return false;
            }

            if ($this->isBetaald()) {
                $message = 'Hoera, het lidgeld is in orde.';
            } else {
                $message = 'Het lidgeld is nog niet volledig betaald.';
            }
            return true;
        }

        return false;
    }

    function getNogTeBetalen() {
        $afrekening = Afrekening::getAfrekening($this->afrekening);

        return '€ '.money_format('%!.2n', min($this->prijs - $this->betaald_cash, $afrekening->getNogTeBetalenFloat()));
    }

    function save() {
        if (!isset($this->id)) {
            return false;
        }

        $id = self::getDb()->escape_string($this->id);
        $betaald_cash = self::getDb()->escape_string($this->betaald_cash);

        $query = "UPDATE inschrijvingen 
                SET 
                 `inschrijving_betaald_cash` = '$betaald_cash'
                 where `inschrijving_id` = '$id'
            ";

        self::getDb()->autocommit(false);
        if (!self::getDb()->query($query)) {
            self::getDb()->autocommit(true);
            return false;
        }

        $afrekening = Afrekening::getAfrekening($this->afrekening);

        if ($afrekening->getNogTeBetalenFloat() < 0) {
            self::getDb()->rollback();
            self::getDb()->autocommit(true);
            return false;
        }

        if (is_null($afrekening)) {
            self::getDb()->autocommit(true);
            return false;
        }

        $this->afrekening_oke = $afrekening->isBetaald();

        // Volgende save functie gebruikt ook autocommit, dus als dat fout gaat wordt ook de query hierboven
        // ongedaan gemaakt. Het zet autocommit ook weer op true, in elke situatie (behalve als id niet klopt)
        return $afrekening->save(); // alles weer juist instellen :D
    }
}