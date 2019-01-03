<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class Product extends Model {
    public $id;
    public $name;
    public $description;

    // Linked fields:
    public $option_sets;
    public $prices;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['product_id'];
        $this->name = $row['product_name'];
        $this->description = $row['product_description'];

        // Todo: loop product prices and product options
       
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        throw new \Exception("Not yet implemented");
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);

        if (!isset($this->description)) {
            $description = 'NULL';
        } else {
            $description = "'".self::getDb()->escape_string($this->description)."'";
        }

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE products 
                SET 
                product_name = '$name',
                product_description = $description,
                 where `product_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                products (`product_name`, `product_description`)
                VALUES ('$name', $description)";
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
                products WHERE `product_id` = '$id' ";

        // Linked tables will get deleted automatically + restricted when orders exist with this product

        return self::getDb()->query($query);
    }
}