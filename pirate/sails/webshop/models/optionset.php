<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;
use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;
class OptionSet extends Model {
    public $id;
    public $name;

    /// Linked (always filled)
    public $options = [];

    private $_delete_options = [];

    public $product = null; // linked should be set on creation

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['set_id'];
        $this->name = $row['set_name'];
    }

    /// Return a list of optionsets
    static function getForProduct($product) {
        $id = self::getDb()->escape_string($product->id);
        $query = 'SELECT os.*, o.* FROM product_option_sets os
        left join product_options o on o.option_set = os.set_id
        WHERE os.set_product = "'.$id.'" order by os.set_id asc, o.option_id asc';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){

                $options = [];
                $optionsets = [];
                while ($row = $result->fetch_assoc()) {
                    if (!isset($optionset) || $optionset->id != $row['set_id']) {
                        if (isset($optionset)) {
                            $optionset->options = $options;
                            $options = [];
                        }
                        $optionset = new OptionSet($row);
                        $optionset->product = $product;
                        $optionsets[] = $optionset;
                    }

                    if (isset($row['option_id'])) {
                        $option = new Option($row);
                        $options[] = $option;
                    }
                }
                // Last remaining
                $optionset->options = $options;
                return $optionsets;
            }
        }

        return [];    
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $new_options = [];

            // Allow product changes
            foreach ($data['options'] as $option_data) {
                if (!empty($option_data['id'])) {
                    foreach($this->options as $option) {
                        if ($option->id == $option_data['id']) {
                            $option_model = $option;
                            break;
                        }
                    }
                    if (!isset($option_model)) {
                        $errors->extend(new ValidationError("De keuze die je wilt aanpassen bestaat niet meer. Kijk na of niet iemand anders ook dit product aan het bewerken is.", "options")); 
                        break;
                    }
                } else {
                    $option_model = new Option();
                }

                try {
                    $option_model->setProperties($option_data);
                    $option_model->optionset = $this;
                    $new_options[] = $option_model;
                } catch (ValidationErrorBundle $bundle) {
                    $errors->extend(...$bundle->getErrors());
                }
            }

            foreach($this->options as $option) {
                if (!in_array($option, $new_options)) {
                    $this->_delete_options[] = $option;
                }
            }

            $this->options = $new_options;

            if (count($this->options) < 2) {
                $errors->extend(new ValidationError("Minimaal twee keuzes nodig", "options")); 
            } 
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
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
            $product = self::getDb()->escape_string($this->product->id);

            $query = "INSERT INTO 
                product_option_sets (`set_name`, `set_product`)
                VALUES ('$name', '$product')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }

            foreach($this->options as $option) {
                if (!$option->save()) {
                    return false;
                }
            }    

            foreach($this->_delete_options as $option) {
                if (!$option->delete()) {
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
                product_option_sets WHERE `set_id` = '$id' ";

        return self::getDb()->query($query);
    }
}