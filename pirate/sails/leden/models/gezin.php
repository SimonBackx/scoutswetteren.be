<?php
namespace Pirate\Sails\Leden\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Sails\Leden\Models\Inschrijving;

class Gezin extends Model {
    public $id;
    public $gezinssituatie;
    public $scouting_op_maat;
    public $scoutsjaar_checked;

    public $ouders = array(); // wordt enkel door speciale toepassingen gebruikt, niet automatisch opgevuld

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['gezin_id'];
        $this->gezinssituatie = $row['gezinssituatie'];
        $this->scouting_op_maat = (intval($row['scouting_op_maat']) == 1);
        $this->scoutsjaar_checked = $row['scoutsjaar_checked'];
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $data['gezinssituatie'] = ucsentence($data['gezinssituatie']);
        $this->gezinssituatie = $data['gezinssituatie'];
        $this->scouting_op_maat = ($data['scouting_op_maat'] == true);
        $this->scoutsjaar_checked = Inschrijving::getScoutsjaar();
        return array();
    }

    static function getGezin($id) {
        if (!is_numeric($id)) {
            return null;
        }

        $id = self::getDb()->escape_string($id);

        $query = '
            SELECT g.* from gezinnen g
            where g.gezin_id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows == 1){
                $row = $result->fetch_assoc();
                return new Gezin($row);
            }
        }
        return null;
    }

    function save() {
        $gezinssituatie = self::getDb()->escape_string($this->gezinssituatie);
        $scouting_op_maat = 0;
        if ($this->scouting_op_maat) {
            $scouting_op_maat = 1;
        }

        if (!isset($this->scoutsjaar_checked)) {
            $scoutsjaar_checked = 'NULL';
        } else {
            $scoutsjaar_checked = "'".self::getDb()->escape_string($this->scoutsjaar_checked)."'";
        }

        if (empty($this->id)) {
            $query = "INSERT INTO 
                gezinnen (`gezinssituatie`,  `scouting_op_maat`, `scoutsjaar_checked`)
                VALUES ('$gezinssituatie', '$scouting_op_maat', $scoutsjaar_checked)";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE gezinnen 
                SET 
                 `gezinssituatie` = '$gezinssituatie',
                 `scouting_op_maat` = '$scouting_op_maat',
                 `scoutsjaar_checked` = $scoutsjaar_checked
                 where gezin_id = '$id' 
            ";
        }

        if (self::getDb()->query($query)) {
            if (empty($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }
        return false;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                gezinnen WHERE gezin_id = '$id' ";


        if (self::getDb()->query($query)) {
           return true;
        }

        return false;
    }

    /// Voeg alle data van een gezin bij elkaar tot één gezin en verwijder het meegegeven gezin
    /// Het meegegeven gezin moet het oudste gezin zijn (minst up to date gezin)
    function merge($gezin) {
        if ($gezin->id == $this->id) {
            // Prevent data loss
            return false;
        }

        $ouders_this = Ouder::getOudersForGezin($this->id);
        $leden_this = Lid::getLedenForGezin($this->id);

        $ouders_other = Ouder::getOudersForGezin($gezin->id);
        $leden_other = Lid::getLedenForGezin($gezin->id);

        /// Merge ouders with same name
        foreach ($ouders_other as $ouder_other) {

            $found = false;
            foreach ($ouders_this as $ouder_this) {
                if ($ouder_this->isProbablyEqual($ouder_other)) {
                    $found = true;
                    if (!$ouder_this->merge($ouder_other)) {
                        return false;
                    }
                    break;
                }
            }

            if (!$found) {
                // Move $ouder_other to this gezin
                $ouder_other->gezin = $this;
                if (!$ouder_other->save()) {
                    return false;
                }
            }
        }

        /// Merge ouders with same name
        foreach ($leden_other as $lid_other) {

            $found = false;
            foreach ($leden_this as $lid_this) {
                if ($lid_this->isProbablyEqual($lid_other)) {
                    $found = true;
                    if (!$lid_this->merge($lid_other)) {
                        return false;
                    }
                    break;
                }
            }

            if (!$found) {
                // Move $ouder_other to this gezin
                $lid_other->gezin = $this;
                if (!$lid_other->save()) {
                    return false;
                }
            }
        }


        /// Update afrekeningen
        $id = self::getDb()->escape_string($gezin->id);
        $new_id = self::getDb()->escape_string($this->id);

        $query = "UPDATE afrekeningen 
            SET 
             `gezin` = '$new_id'
             where gezin = '$id' 
        ";

        if (!self::getDb()->query($query)) {
            return false;
        }

        return $gezin->delete();


    }
    
}