<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;
use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;
use Pirate\Classes\Validating\DatabaseError;

class StripePayment extends Payment {
    public $id;
    public $bank_account; // object
    public $source;
    public $method;
    public $order;
    public $status;

    private $_stripe_source = null;

    static $supported_methods = ['bancontact', 'card'];

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['stripe_id'];
        $this->bank_account = new BankAccount($row);
        $this->source = $row['stripe_source'];
        $this->method = $row['stripe_method'];
        $this->status = $row['stripe_status'];
    }

    /// A function to create a stripe payment based on the source that was provided by the frontend
    /// We can read all needed information in the source object and return an action
    function setProperties($bank_account, $data, $order) {
        if (empty($bank_account->stripe_secret)) {
            throw new ValidationError("Deze betaalmethode wordt niet ondersteund");
        }

        if (!isset($data['method']) || !is_string($data['method'])) {
            throw new ValidationError("method missing");
        }

        if (!in_array($data['method'], static::$supported_methods)) {
            throw new ValidationError("De geselecteerde betaalmethode wordt niet ondersteund.");
        }

        $this->method = $data['method'];
        \Stripe\Stripe::setApiKey($bank_account->stripe_secret);

        // Create the source.

        if ($this->method === 'bancontact') {
            $this->_stripe_source = \Stripe\Source::create([
                "type" => "bancontact",
                "currency" => "eur",
                "amount" => $order->price,
                "owner" => [
                    "name" => $order->user->firstname.' '.$order->user->lastname,
                    "phone" => $order->user->phone,
                    //"email" => $order->user->mail,
                ],
                "redirect" => [
                    "return_url" => $order->getUrl(),
                ],
                "usage" => 'single_use',
            ]);

            // Check status codes: payment_method_not_available, processing_error, invalid_owner_name
            error_log($this->_stripe_source, JSON_PRETTY_PRINT);

        } elseif ($this->method == 'card') {
            if (!isset($data['token']) || !is_string($data['token'])) {
                throw new ValidationError("token missing");
            }

            $this->_stripe_source = \Stripe\Source::create([
                "type" => "card",
                "currency" => "eur",
                "amount" => $order->price,
                "owner" => [
                    "name" => $order->user->firstname.' '.$order->user->lastname,
                    "phone" => $order->user->phone,
                    //"email" => $order->user->mail,
                ],
                "redirect" => [
                    "return_url" => $order->getUrl(),
                ],
                "token" => $data['token'],
                "usage" => 'single_use',
            ]);

            // Check 3D secure
            error_log($this->_stripe_source, JSON_PRETTY_PRINT);

            if (isset($this->_stripe_source->card->three_d_secure) && ($this->_stripe_source->card->three_d_secure == 'required' || $this->_stripe_source->card->three_d_secure == 'recommended'))  {
                error_log( "3D secure has been used");
                $this->_stripe_source = \Stripe\Source::create([
                    "type" => "three_d_secure",
                    "currency" => "eur",
                    "amount" => $order->price,
                    "three_d_secure" => [
                        "card" => $this->_stripe_source->id
                    ],
                    "owner" => [
                        "name" => $order->user->firstname.' '.$order->user->lastname,
                        "phone" => $order->user->phone,
                        //"email" => $order->user->mail,
                    ],
                    "redirect" => [
                        "return_url" => $order->getUrl(),
                    ],
                    "usage" => 'single_use',
                ]);
                error_log($this->_stripe_source, JSON_PRETTY_PRINT);

            }

        }

        $this->bank_account = $bank_account;
        $this->source = $this->_stripe_source->id;
        $this->order = $order;   
        $this->status = 'pending';
        $this->updateStatus();     
    }

    /// Update the status, and charge if possible. Cancel order if possible
    /// The status of the source, one of canceled, chargeable, consumed, failed, or pending. Only chargeable sources can be used to create a charge.
    function updateStatus() {
        $fileName = __DIR__.'/../extra/stripe-lock-'.$this->id.'.txt';
 
        $fp = fopen($fileName, 'w+');
        if (!$fp) return;

        $unlock = function() use ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp); //Unlock the file
        };

        if ($this->status == 'failed' || $this->status == 'canceled' || $this->status == 'consumed') {
            // can't change anymore

            // Unlock and remove file
            $unlock();
            unlink($fileName);

            return;
        }

        // Lock
       
        if (!flock($fp, LOCK_EX)) {
            // Can't create lock
            return;
        }

        try {
            // Check source is chargeable
            \Stripe\Stripe::setApiKey($this->bank_account->stripe_secret);
            $this->_stripe_source = \Stripe\Source::retrieve($this->source);

            //echo '<pre>'.json_encode($this->_stripe_source, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $this->status = $this->_stripe_source->status;

            if ($this->status == 'chargeable') {
                try {
                    $charge = \Stripe\Charge::create([
                        "amount" => $this->order->price,
                        "currency" => "eur",
                        "source" => $this->source,
                    ]);
                    // Charged :D
                    $this->order->markAsPaid();
                    $unlock();
                    $this->updateStatus();
                    return;
                } catch (\Exception $ex) {
                    //echo $ex->getMessage();
                    // Failed to charge
                    // keep status
                }
            } elseif ($this->status == 'failed') {
                $this->order->markAsFailed();
            } elseif ($this->status == 'canceled') {
                $this->order->markAsFailed();
            }

            $this->save();
        } finally {
            $unlock();
        }
    }

    function getName() {
        if ($this->method == 'bancontact') {
            return 'Bancontact';
        }
        return 'Visa / Mastercard';
    }

    function getNextUrl() {
        // todo: Fetch source if not known
        if (isset($this->_stripe_source->redirect->url) && isset($this->_stripe_source->redirect->status) && $this->_stripe_source->redirect->status == 'pending') {
            return $this->_stripe_source->redirect->url;
        }
        return $this->order->getUrl();
    }

    static function getByOrderId($id) {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT p.*, b.* from payment_stripe p
            left join bank_accounts b on b.account_id = p.stripe_bank_account
            where stripe_order = '$id'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    return new StripePayment($row);
                }
            }
        }
        
        return null;
    }

    static function getBySourceId($id) {
        $id = self::getDb()->escape_string($id);
        $query = "SELECT p.*, b.* from payment_stripe p
            left join bank_accounts b on b.account_id = p.stripe_bank_account
            where stripe_source = '$id'";

        if ($result = self::getDb()->query($query)){
            if ($result->num_rows>0){
                while ($row = $result->fetch_assoc()) {
                    $stripe = new StripePayment($row);
                    $order = Order::getById($row['stripe_order']);
                    if (!isset($order)) {
                        return null;
                    }
                    $stripe->order = $order; 
                    return $stripe;
                }
            }
        }
        
        return null;
    }

    function save() {
        $source = self::getDb()->escape_string($this->source);
        $method = self::getDb()->escape_string($this->method);
        $order = self::getDb()->escape_string($this->order->id);
        $bank_account = self::getDb()->escape_string($this->bank_account->id);
        $status = self::getDb()->escape_string($this->status);

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);
            
            $query = "UPDATE payment_stripe
                SET 
                stripe_source = '$source',
                stripe_method = '$method',
                stripe_bank_account = '$bank_account',
                stripe_order = '$order',
                stripe_status = '$status'
                 where `stripe_id` = '$id' 
            ";
        } else {

            $query = "INSERT INTO 
                payment_stripe (`stripe_source`, `stripe_method`, `stripe_order`, `stripe_bank_account`, `stripe_status`)
                VALUES ('$source', '$method', '$order', '$bank_account', '$status')";
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

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                payment_stripe WHERE `stripe_id` = '$id' ";

        return self::getDb()->query($query);
    }
}