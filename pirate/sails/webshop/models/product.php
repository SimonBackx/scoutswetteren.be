<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;
use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

class Product extends Model implements \JsonSerializable {
    public $id;
    public $name;
    public $description;
    public $type;
    public $price_name;

    static $types = [
        'unit' => 'Per stuk', 
        'person' => 'Per persoon', 
        'name' => 'Inschrijving',
    ];

    // Linked fields:
    public $optionsets = null;
    public $prices = null;

    // Helper properties
    private $_delete_prices = [];
    private $_delete_optionsets = [];

    function __construct($row = null) {
        if (is_null($row)) {
            $this->prices = [];
            $this->optionsets = [];
            return;
        }

        $this->id = $row['product_id'];
        $this->name = $row['product_name'];
        $this->description = $row['product_description'];
        $this->type = $row['product_type'];
        $this->price_name = $row['product_price_name'];

        // Todo: loop product prices and product options
       
    }

    static function getById($id) {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT p.*, pp.* FROM products p
        left join product_prices pp on pp.price_product = p.product_id
        WHERE p.product_id = "'.$id.'" order by pp.price_id asc';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){

                $prices = [];
                $product = null;
                while ($row = $result->fetch_assoc()) {
                    if (isset($row['price_id'])) {
                        $price = new ProductPrice($row);
                        $prices[] = $price;
                    }

                    if (!isset($product)) {
                        $product = new Product($row);
                    }
                }

                $product->prices = $prices;
                $product->optionsets = Optionset::getForProduct($product);
                return $product;
            }
        }

        return null;
    }

    function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'price_name' => $this->price_name,
            'prices' => $this->prices,
            'optionsets' => $this->optionsets,
        ];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['description'])) {
            $this->description = $data['description'];
        }

        if (isset($data['price_name'])) {
            $this->price_name = empty($data['price_name']) ? null : $data['price_name'];
        }

        if (isset($data['type'])) {
            if (isset(static::$types[$data['type']])) {
                $this->type = $data['type'];
            } else {
                $errors->extend(new ValidationError("Je hebt geen product type geselecteerd", "type")); 
            }
        }

        if (!is_null($this->prices)) {
            if (isset($data['prices']) && is_array($data['prices'])) {
                $new_prices = [];

                // Allow product changes
                foreach ($data['prices'] as $price_data) {
                    if (!empty($price_data['id'])) {
                        foreach($this->prices as $price) {
                            if ($price->id == $price_data['id']) {
                                $price_model = $price;
                                break;
                            }
                        }
                        if (!isset($price_model)) {
                            $errors->extend(new ValidationError("De product prijs die je wilt aanpassen bestaat niet meer. Kijk na of niet iemand anders ook dit product aan het bewerken is.", "prices")); 
                            break;
                        }
                    } else {
                        $price_model = new ProductPrice();
                    }

                    try {
                        $price_model->setProperties($price_data);
                        $price_model->product = $this;
                        $new_prices[] = $price_model;
                    } catch (ValidationErrorBundle $bundle) {
                        $errors->extend(...$bundle->getErrors());
                    }
                }

                foreach($this->prices as $price) {
                    if (!in_array($price, $new_prices)) {
                        $this->_delete_prices[] = $price;
                    }
                }

                $this->prices = $new_prices;

                if (count($this->prices) < 1) {
                    $errors->extend(new ValidationError("Minimaal één prijs nodig", "prices")); 
                } 
            }
           
        } else {
            if (isset($data['prices'])) {
                $errors->extend(new ValidationError("Aanpassingen aan prijzen niet mogelijk", "prices"));
            }
        }

        if (!is_null($this->optionsets)) {
            if (isset($data['optionsets']) && is_array($data['optionsets'])) {
                $new_optionsets = [];

                // Allow product changes
                foreach ($data['optionsets'] as $optionset_data) {
                    if (!empty($optionset_data['id'])) {
                        foreach($this->optionsets as $optionset) {
                            if ($optionset->id == $optionset_data['id']) {
                                $optionset_model = $optionset;
                                break;
                            }
                        }
                        if (!isset($optionset_model)) {
                            $errors->extend(new ValidationError("Het keuzemenu dat je wilt aanpassen bestaat niet meer. Kijk na of niet iemand anders ook dit product aan het bewerken is.", "optionsets")); 
                            break;
                        }
                    } else {
                        $optionset_model = new OptionSet();
                    }

                    try {
                        $optionset_model->setProperties($optionset_data);
                        $optionset_model->product = $this;
                        $new_optionsets[] = $optionset_model;
                    } catch (ValidationErrorBundle $bundle) {
                        $errors->extend(...$bundle->getErrors());
                    }
                }

                foreach($this->optionsets as $optionset) {
                    if (!in_array($optionset, $new_optionsets)) {
                        $this->_delete_optionsets[] = $optionset;
                    }
                }

                $this->optionsets = $new_optionsets;
            }
           
        } else {
            if (isset($data['optionsets'])) {
                $errors->extend(new ValidationError("Aanpassingen aan keuzemenu's niet mogelijk", "prices"));
            }
        }


        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }

    }

    function save() {
        $name = self::getDb()->escape_string($this->name);
        $type = self::getDb()->escape_string($this->type);

        if (empty($this->description)) {
            $description = 'NULL';
        } else {
            $description = "'".self::getDb()->escape_string($this->description)."'";
        }

        if (!isset($this->price_name)) {
            $price_name = 'NULL';
        } else {
            $price_name = "'".self::getDb()->escape_string($this->price_name)."'";
        }

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE products 
                SET 
                product_name = '$name',
                product_type = '$type',
                product_price_name = $price_name,
                product_description = $description
                 where `product_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                products (`product_name`, `product_type`, `product_price_name`, `product_description`)
                VALUES ('$name', '$type', $price_name, $description)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }

            // Todo: add rollback behaviour
            foreach($this->prices as $price) {
                if (!$price->save()) {
                    return false;
                }
            }

            if (isset($this->optionsets)) {
                foreach($this->optionsets as $optionset) {
                    if (!$optionset->save()) {
                        return false;
                    }
                }    
            }

            foreach($this->_delete_prices as $price) {
                if (!$price->delete()) {
                    return false;
                }
            }

            foreach($this->_delete_optionsets as $optionset) {
                if (!$optionset->delete()) {
                    return false;
                }
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