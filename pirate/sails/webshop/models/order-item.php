<?php
namespace Pirate\Sails\Webshop\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;
use Pirate\Sails\Validating\Classes\DatabaseError;

class OrderItem extends Model implements \JsonSerializable {
    public $id;
    public $amount;
    public $person_name; /// Optional name for certain products
    public $total;

    // Linked fields:
    public $order = null; // object
    public $product = null;
    public $product_price = null;
    public $options = null;

    function __construct($row = null) {
        if (is_null($row)) {
            $this->prices = [];
            $this->optionsets = [];
            return;
        }

        $this->id = $row['item_id'];
        $this->amount = $row['item_amount'];
        $this->person_name = $row['item_person_name'];
        $this->total = $row['item_total'];

        /// Always linked
        $this->product = new Product($row);
        $this->product_price = new ProductPrice($row);
    }

    function jsonSerialize() {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'person_name' => $this->person_name,
            'total' => $this->total
        ];
    }

    static function getById($id) {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT o.*, p.*, pp.*, po.*, pos.* FROM order_items o
        left join products p on p.product_id = o.item_product
        left join product_prices pp on pp.price_id = o.item_product_price
        left join _order_item_options _oop on _oop.order_item_id = o.item_id
        left join product_options po on po.option_id = _oop.product_option_id
        left join product_option_sets pos on pos.set_id = po.option_set

        WHERE o.item_id = "'.$id.'" order by po.option_id asc';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){

                $options = [];
                $item = null;
                while ($row = $result->fetch_assoc()) {
                    if (isset($row['option_id'])) {
                        $option = new Option($row);
                        $options[] = $option;
                    }

                    if (!isset($item)) {
                        $item = new OrderItem($row);
                    }
                }

                $item->options = $options;
                return $item;
            }
        }

        return null;
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();

        if (isset($data['product']['id'])) {
            $this->product = Product::getById($data['product']['id']);
            if (!isset($this->product)) {
                throw new ValidationError("Het product dat je wilt bestellen bestaat niet meer", "product");
            }
        } else {
            throw new ValidationError("Product_id not present", "product.id");
        }

        if (isset($data['price']['id'])) {
            foreach($this->product->prices as $price) {
                if ($price->id == $data['price']['id']) {
                    $this->product_price = $price;
                    break;
                }
            }

            if (!isset($this->product_price)) {
                throw new ValidationError("Een product optie die je wilt bestellen bestaat niet meer. Herlaad de pagina en probeer opnieuw.", "price");
            }
        } else {
            throw new ValidationError("product_price_id not present", "price.id");
        }


        if (isset($data['amount'])) {
            $this->amount = intval($data['amount']);
            if ($this->amount < 1 || $this->amount > 100) {
                $errors->extend(new ValidationError("Invalid amount range", "amount"));
            } elseif ($this->amount != 1 && $this->product->type == "name") {
                $errors->extend(new ValidationError("Amount should be 1 for registrations", "amount"));
            }
        } else {
            $errors->extend(new ValidationError("missing property 'amount'", "amount"));
        }

        if (isset($data['options']) && is_array($data['options'])) {
            $this->options = [];
            
            foreach ($data['options'] as $optionset_id => $option_data) {
                try {
                    if (!is_array($option_data) || !isset($option_data['id'])) {
                        throw new ValidationError("Je hebt niet alle keuzemenu's ingevuld", 'options.'.$optionset_id);
                    }

                    $option = null;
                    foreach ($this->product->optionsets as $optionset) {
                        if ($optionset->id == $optionset_id) {
                            foreach ($optionset->options as $_option) {
                                if ($_option->id == $option_data['id']) {
                                    $option = $_option;
                                    break(2);
                                }
                            }
                        }
                    }

                    if (!isset($option)) {
                        throw new ValidationError("De keuze die je wilt bestellen bestaat niet meer. Herlaad de pagina en probeer het opnieuw", 'options.'+$optionset_id);
                    }
                    
                    $this->options[] = $option;
                } catch (ValidationErrorBundle $bundle) {
                    $errors->extend(...$bundle->getErrors());
                }
            }

            if (count($data['options']) != count($this->product->optionsets)) {
                $errors->extend(new ValidationError("Je hebt niet alle opties doorgestuurd", "options"));
            }
        } else {
            $errors->extend(new ValidationError("items data is missing", "items"));
        }

        if ($this->product->type == "name") {
            if (isset($data['person_name'])) {
                $this->person_name = $data['person_name'];
            } else {
                $errors->extend(new ValidationError("person_name is missing", "person_name"));
            }
        } else {
            if (isset($data['person_name'])) {
                $errors->extend(new ValidationError("person_name is not allowed", "person_name"));
            }
            $this->person_name = null;
        }

        // Calculate total
        $this->total = $this->calculatePrice();
                

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }

    }

    function calculatePrice() {
        $unit_price = $this->product_price->price;
        foreach ($this->options as $option) {
            $unit_price += $option->price_change;
        }
        if ($unit_price < 0) {
            $unit_price = 0;
        }

        return $unit_price * $this->amount;
    }

    function save() {
        if (!isset($this->order->id)) {
            throw new DatabaseError("Can't save with order id");
        }

        $order = self::getDb()->escape_string($this->order->id);
        $amount = self::getDb()->escape_string($this->amount);
        $product = self::getDb()->escape_string($this->product->id);
        $product_price = self::getDb()->escape_string($this->product_price->id);
        $total = self::getDb()->escape_string($this->total);

        if (empty($this->person_name)) {
            $person_name = 'NULL';
        } else {
            $person_name = "'".self::getDb()->escape_string($this->person_name)."'";
        }


        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE order_items 
                SET 
                item_order = '$order',
                item_person_name = $person_name,
                item_amount = '$amount',
                item_product = '$product',
                item_product_price = '$product_price',
                item_total = '$total'
                 where `item_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                order_items (`item_order`, `item_person_name`, `item_amount`, `item_product`, `item_product_price`, `item_total`)
                VALUES ('$order', $person_name, '$amount', '$product', '$product_price', '$total')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }

            // Insert options
            $links = [];
            foreach ($this->options as $option) {
                $links[] = "('$this->id', '$option->id')";
            }
            if (count($links) > 0) {
                $links = implode(', ', $links);
                $query = "INSERT INTO 
                _order_item_options (`order_item_id`, `product_option_id`)
                VALUES $links";

                $result = self::getDb()->query($query);
                if (!$result) {
                    throw new DatabaseError(self::getDb()->error);
                }
            }

            return true;
        }

        throw new DatabaseError(self::getDb()->error);
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                products WHERE `product_id` = '$id' ";

        // Linked tables will get deleted automatically + restricted when orders exist with this product

        return self::getDb()->query($query);
    }

    function getPrice() {
        $int = floor($this->total/100);
        $decimals = str_pad(''.($this->total - $int*100), 2, "0");

        return "€ $int,$decimals";
    }
}