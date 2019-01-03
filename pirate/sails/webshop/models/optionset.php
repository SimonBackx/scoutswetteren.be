<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class OptionSet extends Model {
    public $id;
    public $name;

    /// Linked
    public $options;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['set_id'];
        $this->name = $row['set_name'];

        // Todo: linked options
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        throw new \Exception("Not yet implemented");
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE product_option_sets 
                SET 
                set_name = '$name'
                 where `set_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                product_option_sets (`set_name`)
                VALUES ('$name')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        return false;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                product_option_sets WHERE `price_id` = '$id' ";

        return self::getDb()->query($query);
    }
}