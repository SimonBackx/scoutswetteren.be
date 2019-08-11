<?php
namespace Pirate\Sails\Webshop\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;
class Option extends Model implements \JsonSerializable {
    public $id;
    public $name;
    public $price_change;

    /// Should always get filled!

    public $optionset;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['option_id'];
        $this->name = $row['option_name'];
        $this->price_change = intval($row['option_price_change']);

        // temp
        $this->optionset_id = $row['option_set'];

        if (isset($row['set_id'])) {
            $this->optionset = new OptionSet($row);
        }
    }

    function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price_change' => $this->price_change,
        ];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['price_change'])) {
            $price = str_replace(',', '.', preg_replace("/[^0-9,]/", '', $data['price_change']));

            $neg = 1;
            if (strpos($data['price_change'], '-') !== false) {
                $neg = -1;
            }
            $this->price_change = $neg*floor(floatval($price)*100);
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $price_change = self::getDb()->escape_string($this->price_change);
        $option_set = self::getDb()->escape_string($this->optionset->id);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE product_options
                SET 
                option_name = '$name',
                option_price_change = '$price_change',
                option_set = '$option_set'
                 where `option_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                product_options (`option_name`, `option_price_change`, `option_set`)
                VALUES ('$name', '$price_change', '$option_set')";
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
                product_options WHERE `option_id` = '$id' ";

        return self::getDb()->query($query);
    }

    function getPrice() {
        $price_change = abs($this->price_change);
        $int = floor($price_change/100);
        $decimals = str_pad(''.($price_change - $int*100), 2, "0");

        if ($this->price_change < 0) {
            return "- € $int,$decimals";
        } else {
            return "+ € $int,$decimals";
        }
    }
}