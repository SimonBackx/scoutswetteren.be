<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class ProductPrice extends Model {
    public $id;
    public $name;
    public $price;
    public $type;

    static $types = ['unit', 'person', 'name'];

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['price_id'];
        $this->name = $row['price_name'];
        $this->price = $row['price_price'];
        $this->type = $row['price_type'];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        throw new \Exception("Not yet implemented");
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $type = self::getDb()->escape_string($this->type);
        $price = self::getDb()->escape_string($this->price);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE product_prices 
                SET 
                price_name = '$name',
                price_type = '$type',
                price_price = '$price'
                 where `price_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                product_prices (`price_name`, `price_type`, `price_price`)
                VALUES ('$name', '$type', '$price')";
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
                product_prices WHERE `price_id` = '$id' ";

        return self::getDb()->query($query);
    }
}