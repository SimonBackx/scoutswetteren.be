<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;

class Inschrijving extends Model {
    public $id;
    private $lid;
    public $datum;
    public $scoutsjaar;
    public $tak;
    public $betaald;
    public $betaald_door_scouts;
    public $prijs;
    public $afrekening;

    public static $lidgeld_per_tak = array('kapoenen' => 42, 'wouters' => 32, 'jonggivers' => 32, 'givers' => 32, 'jin' => 32);

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['inschrijving_id'];

        $this->lid = $row['lid'];
        $this->datum = new \DateTime($row['datum']);
        $this->scoutsjaar = $row['scoutsjaar'];
        $this->tak = $row['tak'];
        $this->betaald = $row['betaald'];
        $this->betaald_door_scouts = $row['betaald_door_scouts'];
        $this->afrekening = $row['afrekening'];
        $this->prijs = $row['prijs'];
    }

    // Inschrijvingen vanaf juni verbieden
    static function isInschrijvingsPeriode() {
        $maand = intval(date('n'));
        if ($maand < 9 && $maand >= 6) {
            return false;
        }
        return true;
    }

    static function schrijfIn($lid) {
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
            $inschrijving->lid = $lid->id;
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
}