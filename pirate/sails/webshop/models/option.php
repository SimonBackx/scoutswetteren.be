<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class Option extends Model {
    public $id;
    public $name;
    public $price_change;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['option_id'];
        $this->name = $row['option_name'];
        $this->price_change = $row['option_price_change'];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        throw new \Exception("Not yet implemented");
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $price_change = self::getDb()->escape_string($this->price_change);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE product_options
                SET 
                option_name = '$name',
                option_price_change = '$price_change'
                 where `option_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                product_options (`option_name`, `option_price_change`)
                VALUES ('$name', '$price_change')";
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
                product_options WHERE `price_id` = '$id' ";

        return self::getDb()->query($query);
    }
}