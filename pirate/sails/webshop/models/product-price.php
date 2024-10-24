<?php
namespace Pirate\Sails\Webshop\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;

class ProductPrice extends Model implements \JsonSerializable {
    public $id;
    public $name;
    public $price;

    public $product = null; // linked should be set on creation
  
    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['price_id'];
        $this->name = $row['price_name'];
        $this->price = intval($row['price_price']);
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['price'])) {
            $price = str_replace(',', '.', preg_replace("/[^0-9,]/", '', $data['price']));
            $this->price = floor(floatval($price)*100);
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    function getPrice() {
        $int = floor($this->price/100);
        $decimals = str_pad(''.($this->price - $int*100), 2, "0");

        return "€ $int,$decimals";
    }

    function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ];
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $price = self::getDb()->escape_string($this->price);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE product_prices 
                SET 
                price_name = '$name',
                price_price = '$price'
                 where `price_id` = '$id' 
            ";
        } else {
            $product_id = self::getDb()->escape_string($this->product->id);

            $query = "INSERT INTO 
                product_prices (`price_name`, `price_price`, `price_product`)
                VALUES ('$name', '$price', '$product_id')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        } else {
            echo self::getDb()->error;
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