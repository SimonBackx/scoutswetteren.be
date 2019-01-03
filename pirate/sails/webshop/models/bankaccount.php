<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;

class BankAccount extends Model {
    public $id;
    public $name;
    public $iban;
    public $stripe;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['account_id'];
        $this->name = $row['account_name'];
        $this->iban = $row['account_iban'];
        $this->stripe = $row['account_stripe'];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        throw new \Exception("Not yet implemented");
    }

    static function getAll() {
        $accounts = array();
        $query = '
            SELECT * from bank_accounts
            order by account_name';


        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $accounts[] = new BankAccount($row);
                }
            }
        }
        
        return $accounts;
    }

    static function getById($id) {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT * from bank_accounts
            where account_id = '$id'
            order by account_name";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    return new BankAccount($row);
                }
            }
        }
        
        return null;
    }

    function save() {
        $name = self::getDb()->escape_string($this->name);

        if (!isset($this->iban)) {
            $iban = 'NULL';
        } else {
            $iban = "'".self::getDb()->escape_string($this->iban)."'";
        }

        if (!isset($this->stripe)) {
            $stripe = 'NULL';
        } else {
            $stripe = "'".self::getDb()->escape_string($this->stripe)."'";
        }


        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE bank_accounts
                SET 
                account_name = '$name',
                account_iban = $iban,
                account_stripe = $stripe
                 where `account_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                bank_accounts (`account_name`, `account_iban`, `account_stripe`)
                VALUES ('$name', $iban, $stripe)";
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
                bank_accounts WHERE `account_id` = '$id' ";

        return self::getDb()->query($query);
    }
}