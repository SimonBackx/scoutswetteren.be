<?php
namespace Pirate\Model\Settings;
use Pirate\Model\Model;

class Setting extends Model {
    public $id;
    public $key;
    public $value;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['id'];
        $this->key = $row['key'];
        $this->value = $row['value'];
    }

    static function getSetting($key, $default = null) {
        $key = self::getDb()->escape_string($key);

        $query = "SELECT * from settings
        where `key` = '$key'";

        if ($result = self::getDb()->query($query)){
            $row = $result->fetch_assoc();
            return new Setting($row);
        }

        return new Setting(array('key' => $key, 'value' => $default, 'id' => null));
    }


    function save(){
        $key = self::getDb()->escape_string($this->key);

        if (isset($this->value)) {
            $value = "'".self::getDb()->escape_string($this->value)."'";
        } else {
            $value = "NULL";
        }

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE settings 
                SET 
                 `key` = '$key',
                 `value` = $value
                 where id = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                settings (`key`, `value`)
                VALUES ('$key', $value)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
        } 

        return $result;
    }

    function delete() {
        if (!isset($this->id)) {
            return false;
        }
        
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                settings WHERE id = '$id' ";

        return self::getDb()->query($query);
    }
}