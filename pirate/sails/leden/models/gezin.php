<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;

class Gezin extends Model {
    public $id;
    public $gezinssituatie;
    public $scouting_op_maat;


    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['gezin_id'];
        $this->gezinssituatie = $row['gezinssituatie'];
        $this->scouting_op_maat = intval($row['scouting_op_maat']);
    }

    // empty array on success
    // array of errors on failure
    function setProperties(&$data) {
        $data['gezinssituatie'] = ucsentence($data['gezinssituatie']);
        $this->gezinssituatie = $data['gezinssituatie'];
        $this->scouting_op_maat = ($data['scouting_op_maat'] == true);
        return array();
    }

    function save() {
        $gezinssituatie = self::getDb()->escape_string($this->gezinssituatie);
        $scouting_op_maat = 0;
        if ($this->scouting_op_maat) {
            $scouting_op_maat = 1;
        }

        if (empty($this->id)) {
            $query = "INSERT INTO 
                gezinnen (`gezinssituatie`,  `scouting_op_maat`)
                VALUES ('$gezinssituatie', '$scouting_op_maat')";
        } else {
            $id = self::getDb()->escape_string($this->id);
            $query = "UPDATE events 
                SET 
                 `gezinssituatie` = '$gezinssituatie',
                 `scouting_op_maat` = '$scouting_op_maat'
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