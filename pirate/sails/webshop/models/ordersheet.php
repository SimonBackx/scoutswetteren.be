<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class OrderSheet extends Model {
    public $id;
    public $name;
    public $subtitle;
    public $description;
    public $due_date;
    public $bank_account; // object

    // Not always filled
    public $products = []; 

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['sheet_id'];
        $this->name = $row['sheet_name'];
        $this->subtitle = $row['sheet_subtitle'];
        $this->description = $row['sheet_description'];
        
        $this->due_date = isset($row['sheet_due_date']) ? new \DateTime($row['sheet_due_date']) : null;

        $this->bank_account = new BankAccount($row);
    }

    static function getById($id) {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT o.*, b.*, p.* FROM order_sheets o
        left join bank_accounts b on b.account_id = o.sheet_bank_account
        left join _order_sheet_products _o_p on _o_p.order_sheet_id = o.sheet_id
        left join products p on _o_p.product_id = p.product_id
        WHERE o.sheet_id = "'.$id.'"';

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){

                $products = [];
                $order_sheet = null;
                while ($row = $result->fetch_assoc()) {
                    if (isset($row['product_id'])) {
                        $product = new Product($row);
                        $products[] = $product;
                    }

                    if (!isset($order_sheet)) {
                        $order_sheet = new OrderSheet($row);
                    }
                }

                $order_sheet->products = $products;
                return $order_sheet;
            }
        }

        return null;
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        if (isset($data['order_sheet_account'])) {
            $bank_account = BankAccount::getById($data['order_sheet_account']);
            if (!isset($bank_account)) {
                throw new \Exception("Ongeldige bankrekening"); 
            }
            $this->bank_account = $bank_account;
        }

        if (isset($data['order_sheet_description'])) {
            $this->description = empty($data['order_sheet_description']) ? null : $data['order_sheet_description'];
        }

        if (isset($data['order_sheet_due_date'])) {
            if (empty($data['order_sheet_due_date'])) {
                $this->due_date = null;
            } else {
                $due_date = \DateTime::createFromFormat('d-m-Y', $data['order_sheet_due_date']);
                if ($due_date !== false) {
                    $this->due_date = clone $due_date;
                } else {
                    throw new \Exception('Ongeldige deadline voor aankopen');
                }
            } 
        }

        // valid
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);

        if (!isset($this->due_date)) {
            $due_date = 'NULL';
        } else {
            $due_date = "'".self::getDb()->escape_string($this->due_date->format('Y-m-d'))."'";
        }

        if (!isset($this->description)) {
            $description = 'NULL';
        } else {
            $description = "'".self::getDb()->escape_string($this->description)."'";
        }

        if (!isset($this->subtitle)) {
            $subtitle = 'NULL';
        } else {
            $subtitle = "'".self::getDb()->escape_string($this->subtitle)."'";
        }

        if (!isset($this->bank_account)) {
            return false;
        }

        $bank_account = self::getDb()->escape_string($this->bank_account->id);


        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE order_sheets
                SET 
                sheet_name = '$name',
                sheet_subtitle = $subtitle,
                sheet_description = $description,
                sheet_bank_account = '$bank_account',
                sheet_due_date = $due_date
                 where `sheet_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                order_sheets (`sheet_name`, `sheet_subtitle`, `sheet_description`, `sheet_bank_account`, `sheet_due_date`)
                VALUES ('$name', $subtitle, $description, '$bank_account', $due_date)";
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

    function linkProduct($product) {
        $order_sheet_id = self::getDb()->escape_string($this->id);
        $product_id = self::getDb()->escape_string($product->id);
        
        $query = "INSERT INTO 
                _order_sheet_products (`product_id`, `order_sheet_id`)
                VALUES ('$product_id', '$order_sheet_id')";

        $result = self::getDb()->query($query);

        if ($result) {
            $this->products[] = $product;
            return true;
        }

        return false;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                order_sheets WHERE `sheet_id` = '$id' ";

        return self::getDb()->query($query);
    }
}