<?php
namespace Pirate\Sails\Webshop\Models;

use Pirate\Wheel\Model;

class BankAccount extends Model
{
    public $id;
    public $name;
    public $iban;
    public $stripe_public;
    public $allow_cash;

    public function __construct($row = null)
    {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['account_id'];
        $this->name = $row['account_name'];
        $this->iban = $row['account_iban'];
        $this->stripe_public = $row['account_stripe_public'];
        $this->stripe_secret = $row['account_stripe_secret'];
        $this->allow_cash = $row['account_allow_cash'] ? true : false;

    }

    /// Set the properties of this model. Throws an error if the data is not valid
    public function setProperties(&$data)
    {
        throw new \Exception("Not yet implemented");
    }

    public function getPaymentMethods()
    {
        $d = [];

        if ($this->allow_cash) {
            $d[] = 'cash';
        }

        /// Currently only stripe supported
        if (empty($this->stripe_public)) {
            $d[] = 'transfer';
        } else {
            $d[] = 'stripe';
            $d[] = 'transfer';
        }
        return $d;
    }

    public static function getAll()
    {
        $accounts = array();
        $query = '
            SELECT * from bank_accounts
            order by account_name';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $accounts[] = new BankAccount($row);
                }
            }
        }

        return $accounts;
    }

    public static function getById($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT * from bank_accounts
            where account_id = '$id'
            order by account_name";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    return new BankAccount($row);
                }
            }
        }

        return null;
    }

    public function save()
    {
        $name = self::getDb()->escape_string($this->name);

        if (!isset($this->iban)) {
            $iban = 'NULL';
        } else {
            $iban = "'" . self::getDb()->escape_string($this->iban) . "'";
        }

        if (!isset($this->stripe_public)) {
            $stripe_public = 'NULL';
        } else {
            $stripe_public = "'" . self::getDb()->escape_string($this->stripe_public) . "'";
        }

        if (!isset($this->stripe_secret)) {
            $stripe_secret = 'NULL';
        } else {
            $stripe_secret = "'" . self::getDb()->escape_string($this->stripe_secret) . "'";
        }

        $allow_cash = self::getDb()->escape_string($this->allow_cash ? 1 : 0);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE bank_accounts
                SET
                account_name = '$name',
                account_iban = $iban,
                account_stripe_public = $stripe_public,
                account_stripe_secret = $stripe_secret,
                account_allow_cash = '$allow_cash'

                 where `account_id` = '$id'
            ";
        } else {

            $query = "INSERT INTO
                bank_accounts (`account_name`, `account_iban`, `account_stripe_public`, `account_stripe_secret`, `account_allow_cash`)
                VALUES ('$name', $iban, $stripe_public, $stripe_secret, '$allow_cash')";
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

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                bank_accounts WHERE `account_id` = '$id' ";

        return self::getDb()->query($query);
    }
}
