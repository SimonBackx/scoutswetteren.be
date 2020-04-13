<?php
namespace Pirate\Sails\Webshop\Models;

use Pirate\Sails\AmazonSes\Models\Mail;
use Pirate\Sails\Validating\Classes\DatabaseError;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Wheel\Model;

class Order extends Model implements \JsonSerializable
{
    public $id;
    public $price;
    public $payment_method;
    public $created_at;
    public $failed_at;
    public $paid_at;
    public $user;
    public $secret;
    public $valid;
    public $order_sheet_id;

    // Linked fields:
    public $items = null;

    // only for creation
    public $payment = null;
    public $order_sheet = null; // only used on creation

    public function __construct($row = null)
    {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['order_id'];
        $this->price = intval($row['order_price']);
        $this->payment_method = $row['order_payment_method'];
        $this->created_at = new \DateTime($row['order_created_at']);
        $this->paid_at = isset($row['order_paid_at']) ? new \DateTime($row['order_paid_at']) : null;
        $this->failed_at = isset($row['order_failed_at']) ? new \DateTime($row['order_failed_at']) : null;
        $this->user = new OrderUser($row);
        $this->secret = $row['order_secret'];
        $this->valid = ($row['order_valid'] == 1);
        $this->order_sheet_id = $row['order_sheet'];
    }

    public function fetchOrderSheet()
    {
        if (!isset($this->order_sheet) && isset($this->order_sheet_id)) {
            $this->order_sheet = OrderSheet::getById($this->order_sheet_id);
        }
    }

    public function fetchPayment()
    {
        if ($this->payment_method == 'stripe') {
            $this->payment = StripePayment::getByOrderId($this->id);
            if (isset($this->payment)) {
                $this->payment->order = $this;
            }
        } elseif ($this->payment_method == 'transfer') {
            $this->payment = TransferPayment::getByOrderId($this->id);
            if (isset($this->payment)) {
                $this->payment->order = $this;
            }
        }
    }

    public function getSummary()
    {
        $persons = [];
        $items = [];
        foreach ($this->items as $item) {
            if (isset($item->person_name)) {
                $persons[] = $item->person_name;
            } else {
                $amount = '';
                if ($item->product->type == 'unit' && $item->amount > 1) {
                    $amount = "$item->amount x ";
                } elseif ($item->product->type == 'person') {
                    $amount = "$item->amount personen x ";
                }
                if (isset($item->product->price_name)) {
                    $items[] = $amount . $item->product->name . ' (' . $item->product_price->name . ')';
                } else {
                    $items[] = $amount . $item->product->name;
                }
            }
        }
        return [
            "id" => $this->id,
            "url" => $this->getUrl(),
            "date" => isset($this->created_at) ? 'Geplaatst op ' . $this->created_at->format('d/m/Y') . ' om ' . $this->created_at->format('H:i') : '',
            "persons" => $persons,
            "items" => $items,
        ];
    }

    public function isRegistration()
    {
        return count($this->getSummary()['persons']) > 0;
    }

    public static function getById($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT o.*, u.*, o_i.* FROM orders o
        left join order_users u on u.order_user_id = o.order_user
        left join order_items o_i on o_i.item_order = o.order_id
        WHERE o.order_id = "' . $id . '" order by o_i.item_id asc';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {

                $items = [];
                $order = null;
                while ($row = $result->fetch_assoc()) {
                    if (isset($row['item_id'])) {
                        $item = OrderItem::getById($row['item_id']);
                        if (isset($item)) {
                            $items[] = $item;
                        }
                    }

                    if (!isset($order)) {
                        $order = new Order($row);
                    }
                }

                $order->items = $items;
                $order->fetchPayment();

                return $order;
            }
        }

        return null;
    }

    // where paid
    public static function getByOrderSheet($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT o.*, u.*, o_i.* FROM orders o
        left join order_users u on u.order_user_id = o.order_user
        left join order_items o_i on o_i.item_order = o.order_id
        WHERE
        o.order_sheet = "' . $id . '"
        and o.order_valid = 1
        order by o.order_id, o_i.item_id asc';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {

                $order = null;
                $orders = [];
                while ($row = $result->fetch_assoc()) {
                    if (!isset($order) || $order->id != $row['order_id']) {
                        $order = new Order($row);
                        $order->fetchPayment();
                        $order->items = [];
                        $orders[] = $order;
                    }
                    if (isset($row['item_id'])) {
                        $item = OrderItem::getById($row['item_id']);
                        if (isset($item)) {
                            $order->items[] = $item;
                        }
                    }
                }

                return $orders;
            }
        }

        return [];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    public function setProperties(&$data)
    {
        $errors = new ValidationErrors();

        if (isset($this->id)) {
            throw new ValidationError("Order is not editable");
        }

        if (isset($data['user']) && is_array($data['user'])) {
            try {
                $this->user = new OrderUser();
                $this->user->setProperties($data['user'], $this->order_sheet->delivery);
            } catch (ValidationErrorBundle $bundle) {
                $errors->extend(...$bundle->getErrors());
            }
        } else {
            $errors->extend(new ValidationError("User data is missing", "user"));
        }

        // Items
        if (isset($data['items']) && is_array($data['items'])) {
            $this->items = [];
            $this->price = 0;

            foreach ($data['items'] as $item) {
                try {
                    $item_model = new OrderItem();
                    $item_model->setProperties($item);
                    $this->items[] = $item_model;
                    $item_model->order = $this;
                    $this->price += $item_model->total;
                } catch (ValidationErrorBundle $bundle) {
                    $errors->extend(...$bundle->getErrors());
                }
            }

            if ($this->order_sheet->delivery) {
                if ($this->user->zipcode) {
                    if (intval($this->user->zipcode) < 9000) {
                        $this->price += 200;
                    }
                }
            }

            if (count($this->items) == 0) {
                $errors->extend(new ValidationError("items should not be empty", "items"));
            }
        } else {
            $errors->extend(new ValidationError("items data is missing", "items"));
        }

        if (isset($data['price'])) {
            if (intval($data['price']) != $this->price) {
                if (count($errors->getErrors()) == 0) {
                    $errors->extend(new ValidationError("Price does not match server side calculated price", "price"));
                }
            }
        } else {
            $errors->extend(new ValidationError("Price is missing", "price"));
        }

        if (isset($data['payment_method']['type'], $data['payment_method']['data']) && is_array($data['payment_method']['data'])) {
            $type = $data['payment_method']['type'];

            try {
                if ($type === 'stripe') {
                } elseif ($type === 'transfer') {
                } elseif ($type === 'cash') {
                } else {
                    throw new ValidationError("Ongeldige betaalmethode geselecteerd", "payment_method");
                }
                $this->payment_method = $type;
            } catch (ValidationErrorBundle $bundle) {
                $errors->extend(...$bundle->getErrors());
            }
        } else {
            $errors->extend(new ValidationError("payment_method is missing", "payment_method"));
        }

        $this->secret = $this->generateLongKey();
        $this->valid = false;

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    /// After creating the order, you need to initialise the payment settings
    public function setPayment(&$data, $bank_account)
    {
        $errors = new ValidationErrors();
        if (isset($data['payment_method']['type'], $data['payment_method']['data']) && is_array($data['payment_method']['data'])) {
            $type = $data['payment_method']['type'];

            try {
                if (!in_array($type, $bank_account->getPaymentMethods())) {
                    throw new ValidationError("Ongeldige betaalmethode geselecteerd", "payment_method");
                }

                if ($type === 'cash') {
                    $this->payment = null;
                    $this->markAsValid();
                } else {
                    if ($type === 'stripe') {
                        $this->payment = new StripePayment();
                    } elseif ($type === 'transfer') {
                        $this->payment = new TransferPayment();

                    } else {
                        throw new ValidationError("Ongeldige betaalmethode geselecteerd", "payment_method");
                    }
                    $this->payment->setProperties($bank_account, $data['payment_method']['data'], $this);
                    $this->payment->save();
                }

                $this->payment_method = $type;

            } catch (ValidationErrorBundle $bundle) {
                $errors->extend(...$bundle->getErrors());
            }

            // Validate
        } else {
            $errors->extend(new ValidationError("payment_method is missing", "payment_method"));
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    public function isPaid()
    {
        return isset($this->paid_at);
    }

    public function isValid()
    {
        return $this->valid === true;
    }

    public function isFailed()
    {
        if (isset($this->failed_at)) {
            $this->fetchOrderSheet();
            return true;
        }
        return false;
    }

    public function markAsPaid()
    {
        $this->paid_at = new \DateTime();
        $this->save();

        if (!$this->isValid()) {
            $this->markAsValid();
        }
    }

    public function markAsValid()
    {
        if ($this->valid) {
            return;
        }
        $this->valid = true;
        $this->save();

        /// Send an e-mail to the user with order details
        if (!$this->isPaid()) {
            // Send an email with information about how to make the payment
            if ($this->isRegistration()) {
                $template = 'valid-registration-not-paid';
            } else {
                $template = 'valid-order-not-paid';
            }

        } else {
            // Send an email that we received the payment and the order is complete
            if ($this->isRegistration()) {
                $template = 'valid-registration-paid';
            } else {
                $template = 'valid-order-paid';
            }
        }

        $this->fetchOrderSheet();

        $mail = Mail::create(
            isset($this->order_sheet) ? $this->order_sheet->name : ($this->isRegistration() ? 'Jouw inschrijving' : 'Jouw bestelling'),
            $template,
            array(
                'url' => $this->getUrl(),
                'order_sheet' => isset($this->order_sheet) ? $this->order_sheet : null,
                'order' => $this,
            )
        );

        $mail->addTo(
            $this->user->mail,
            array(
                'firstname' => $this->user->firstname,
            ),
            $this->user->firstname . ' ' . $this->user->lastname
        );

        $mail->sendOrDelay();

    }

    public function markAsFailed()
    {
        $this->valid = false;
        $this->failed_at = new \DateTime();
        $this->save();

        // Send an e-mail that the payment failed

        // If paid: refund (todo)
    }

    private static function generateLongKey()
    {
        $bytes = openssl_random_pseudo_bytes(32);
        return bin2hex($bytes);
    }

    public function save()
    {
        // Save user
        if (!isset($this->user) || !$this->user->save()) {
            return false;
        }

        if (!isset($this->items)) {
            return false;
        }

        $price = self::getDb()->escape_string($this->price);
        $payment_method = self::getDb()->escape_string($this->payment_method);
        $secret = self::getDb()->escape_string($this->secret);
        $user = self::getDb()->escape_string($this->user->id);

        if (empty($this->paid_at)) {
            $paid_at = 'NULL';
        } else {
            $paid_at = "'" . self::getDb()->escape_string($this->paid_at->format("Y-m-d H:i:s")) . "'";
        }

        if (empty($this->failed_at)) {
            $failed_at = 'NULL';
        } else {
            $failed_at = "'" . self::getDb()->escape_string($this->failed_at->format("Y-m-d H:i:s")) . "'";
        }

        $valid = ($this->valid === true) ? '1' : '0';

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE orders
                SET
                order_price = '$price',
                order_payment_method = '$payment_method',
                order_secret = '$secret',
                order_valid = '$valid',
                order_user = '$user',
                order_paid_at = $paid_at,
                order_failed_at = $failed_at
                 where `order_id` = '$id'
            ";
        } else {
            $created_at = self::getDb()->escape_string((new \DateTime())->format("Y-m-d H:i:s"));

            if (!isset($this->order_sheet->id)) {
                $order_sheet = 'NULL';
            } else {
                $order_sheet = "'" . self::getDb()->escape_string($this->order_sheet->id) . "'";
            }

            $query = "INSERT INTO
                orders (`order_price`, `order_payment_method`, `order_secret`, `order_valid`, `order_user`, `order_paid_at`, `order_created_at`, `order_failed_at`, `order_sheet`)
                VALUES ('$price', '$payment_method', '$secret', '$valid', '$user', $paid_at, '$created_at', $failed_at, $order_sheet)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;

                // Todo: add rollback behaviour
                foreach ($this->items as $item) {
                    if (!$item->save()) {
                        return false;
                    }
                }
            }

            return true;
        }

        throw new DatabaseError(self::getDb()->error);
    }

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                products WHERE `product_id` = '$id' ";

        // Linked tables will get deleted automatically + restricted when orders exist with this product

        return self::getDb()->query($query);
    }

    public function getPaymentName() {
        if ($this->payment_method == 'cash') {
            if ($this->isRegistration()) {
                return "Betalen bij binnenkomen";
            } else {
                return "Betalen bij afhalen";
            }
        }
        return $this->payment->getName();
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'payment_method' => $this->payment_method,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'paid_at' => $this->paid_at->format('Y-m-d H:i:s'),
            //'user' => $user,
            'secret' => $this->secret,
            'valid' => $this->valid,
            'items' => $this->items,
        ];
    }

    public function getPrice()
    {
        $int = floor($this->price / 100);
        $decimals = str_pad('' . ($this->price - $int * 100), 2, "0");

        return "€ $int,$decimals";
    }

    public function getUrl()
    {
        return "https://{$_SERVER['SERVER_NAME']}/order/$this->id/$this->secret";
    }

}
