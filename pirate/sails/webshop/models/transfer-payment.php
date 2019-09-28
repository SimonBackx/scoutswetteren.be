<?php
namespace Pirate\Sails\Webshop\Models;

use Pirate\Sails\Validating\Classes\DatabaseError;
use Pirate\Sails\Validating\Classes\ValidationError;

class TransferPayment extends Payment
{
    public $id;
    public $bank_account; // object
    public $order; // not set in constructor
    public $reference; // description of transfer
    public $status;

    public function __construct($row = null)
    {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['transfer_id'];
        $this->bank_account = new BankAccount($row);
        $this->status = $row['transfer_status'];
        $this->reference = $row['transfer_reference'];
        $this->order_id = $row['transfer_order'];
    }

    public function fetchOrder()
    {
        if (!isset($this->order) && isset($this->order_id)) {
            $this->order = Order::getById($this->order_id);
        }
    }

    /// A function to create a transfer payment based on the source that was provided by the frontend
    /// We can read all needed information in the source object and return an action
    public function setProperties($bank_account, $data, $order)
    {
        if (empty($bank_account->iban)) {
            throw new ValidationError("Deze betaalmethode wordt niet ondersteund");
        }

        $this->order = $order;
        $this->reference = $this->order->isRegistration() ? "Inschrijving {$this->order->id}" : "Bestelling {$this->order->id}";
        $this->bank_account = $bank_account;
        $this->status = 'pending';
        $this->updateStatus();
    }

    public function getName()
    {
        return 'Overschrijving';
    }

    public function updateStatus()
    {
        // Can't update sync
    }

    public function confirm()
    {
        $this->status = 'confirmed';
        $this->order->markAsValid();
        $this->save();
    }

    public function getNextUrl()
    {
        // Todo: seperate transfer page!
        return $this->order->getUrl();
    }

    public static function getById($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT p.*, b.* from payment_transfer p
            left join bank_accounts b on b.account_id = p.transfer_bank_account
            where transfer_id = '$id'";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $t = new TransferPayment($row);
                    $t->fetchOrder();
                    return $t;
                }
            }
        }

        return null;
    }

    public static function getByOrderId($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT p.*, b.* from payment_transfer p
            left join bank_accounts b on b.account_id = p.transfer_bank_account
            where transfer_order = '$id'";

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    return new TransferPayment($row);
                }
            }
        }

        return null;
    }

    public function save()
    {
        $order = self::getDb()->escape_string($this->order->id);
        $bank_account = self::getDb()->escape_string($this->bank_account->id);
        $status = self::getDb()->escape_string($this->status);
        $reference = self::getDb()->escape_string($this->reference);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE payment_transfer
                SET
                transfer_bank_account = '$bank_account',
                transfer_order = '$order',
                transfer_reference = '$reference',
                transfer_status = '$status'
                 where `transfer_id` = '$id'
            ";
        } else {

            $query = "INSERT INTO
                payment_transfer (`transfer_order`, `transfer_bank_account`, `transfer_status`, `transfer_reference`)
                VALUES ('$order', '$bank_account', '$status', '$reference')";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return;
        }

        throw new DatabaseError(self::getDb()->error);
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                payment_transfer WHERE `transfer_id` = '$id' ";

        return self::getDb()->query($query);
    }

    public function getConfirmUrl()
    {
        return "https://{$_SERVER['SERVER_NAME']}/transfer-payment/$this->id/confirm";
    }

    public function getCancelUrl()
    {
        return "https://{$_SERVER['SERVER_NAME']}/transfer-payment/$this->id/cancel";
    }
}
