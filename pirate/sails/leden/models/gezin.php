<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Inschrijving;

class Gezin extends Model {
    public $id;
    public $gezinssituatie;
    public $scouting_op_maat;
    public $scoutsjaar_checked;

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

        if (!isset($scoutsjaar_checked)) {
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


    
}