<?php
namespace Pirate\Sails\Webshop\Models;

use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Model;

class OrderSheet extends Model implements \JsonSerializable
{
    public $id;
    public $name;
    public $subtitle;
    public $description;
    public $due_date;
    public $type;
    public $bank_account; // object

    // Not always filled
    public $products = [];

    static $types = [
        'registrations' => 'Inschrijvingen',
        'orders' => 'Bestellingen',
    ];

    public function getButtonName()
    {
        return static::$types[$this->type];
    }

    public function __construct($row = null)
    {
        if (is_null($row)) {
            return;
        }

        $this->id = $row['sheet_id'];
        $this->name = $row['sheet_name'];
        $this->subtitle = $row['sheet_subtitle'];
        $this->description = $row['sheet_description'];
        $this->type = $row['sheet_type'];

        $this->due_date = isset($row['sheet_due_date']) ? new \DateTime($row['sheet_due_date']) : null;

        $this->phone = $row['sheet_phone'];
        $this->mail = $row['sheet_mail'];

        $this->bank_account = new BankAccount($row);
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'phone' => $this->phone,
            'mail' => $this->mail,
            'description' => $this->description,
            'type' => $this->type,
            'due_date' => empty($this->due_date) ? null : $this->due_date->format('Y-m-d'),
            'products' => $this->products,
        ];
    }

    public static function getById($id)
    {
        $id = self::getDb()->escape_string($id);
        $query = 'SELECT o.*, b.*, p.* FROM order_sheets o
        left join bank_accounts b on b.account_id = o.sheet_bank_account
        left join _order_sheet_products _o_p on _o_p.order_sheet_id = o.sheet_id
        left join products p on _o_p.product_id = p.product_id
        WHERE o.sheet_id = "' . $id . '"';

        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0) {

                $products = [];
                $order_sheet = null;
                while ($row = $result->fetch_assoc()) {
                    if (isset($row['product_id'])) {
                        $product = Product::getById($row['product_id']); //new Product($row);
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
    public function setProperties(&$data)
    {
        $errors = new ValidationErrors();

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

        if (isset($data['order_sheet_type'])) {
            if (isset(static::$types[$data['order_sheet_type']])) {
                $this->type = $data['order_sheet_type'];
            } else {
                $errors->extend(new ValidationError("Je hebt geen formuliertype geselecteerd", "type"));
            }
        }

        if (isset($data['order_sheet_due_date'])) {
            if (empty($data['order_sheet_due_date'])) {
                $this->due_date = null;
            } else {
                $due_date = \DateTime::createFromFormat('d-m-Y', $data['order_sheet_due_date']);
                if ($due_date !== false) {
                    $this->due_date = clone $due_date;
                } else {
                    $errors->extend(new ValidationError('Ongeldige deadline voor aankopen'));
                }
            }
        }

        if (isset($data['order_sheet_mail']) && !empty($data['order_sheet_mail'])) {
            if (!Validator::isValidMail($data['order_sheet_mail'])) {
                $errors->extend(new ValidationError('Ongeldig e-mailadres'));
            } else {
                $this->mail = $data['order_sheet_mail'];
            }
        } else {
            $this->mail = null;
        }

        if (isset($data['order_sheet_phone']) && strlen($data['order_sheet_phone']) > 0) {
            $errors_list = [];
            if (!Validator::validatePhone($data['order_sheet_phone'], $this->phone, $errors_list)) {
                foreach ($errors_list as $error) {
                    $errors->extend(new ValidationError($error));
                }
            }

        } else {
            $this->phone = null;
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    public function isDue()
    {
        if (!isset($this->due_date)) {
            return false;
        }
        $due = $this->due_date->format('Y-m-d');
        $today = (new \DateTime())->format('Y-m-d');

        return $due < $today;
    }

    public function getDueText()
    {
        if (!isset($this->due_date)) {
            return '';
        }
        $due = $this->due_date->format('d/m/Y');

        if ($this->type == "registrations") {
            return "Inschrijven mogelijk tot $due";
        } else {
            return "Bestellen mogelijk tot $due";
        }
    }

    public function save()
    {
        $name = self::getDb()->escape_string($this->name);
        $type = self::getDb()->escape_string($this->type);

        if (!isset($this->due_date)) {
            $due_date = 'NULL';
        } else {
            $due_date = "'" . self::getDb()->escape_string($this->due_date->format('Y-m-d')) . "'";
        }

        if (!isset($this->description)) {
            $description = 'NULL';
        } else {
            $description = "'" . self::getDb()->escape_string($this->description) . "'";
        }

        if (!isset($this->subtitle)) {
            $subtitle = 'NULL';
        } else {
            $subtitle = "'" . self::getDb()->escape_string($this->subtitle) . "'";
        }

        if (!isset($this->bank_account)) {
            return false;
        }

        $bank_account = self::getDb()->escape_string($this->bank_account->id);

        if (!isset($this->phone)) {
            $phone = 'NULL';
        } else {
            $phone = "'" . self::getDb()->escape_string($this->phone) . "'";
        }

        if (!isset($this->mail)) {
            $mail = 'NULL';
        } else {
            $mail = "'" . self::getDb()->escape_string($this->mail) . "'";
        }

        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE order_sheets
                SET
                sheet_name = '$name',
                sheet_type = '$type',
                sheet_subtitle = $subtitle,
                sheet_description = $description,
                sheet_bank_account = '$bank_account',
                sheet_due_date = $due_date,
                sheet_phone = $phone,
                sheet_mail = $mail
                 where `sheet_id` = '$id'
            ";
        } else {

            $query = "INSERT INTO
                order_sheets (`sheet_name`, `sheet_type`, `sheet_subtitle`, `sheet_description`, `sheet_bank_account`, `sheet_due_date`, `sheet_phone`, `sheet_mail`)
                VALUES ('$name', '$type', $subtitle, $description, '$bank_account', $due_date, $phone, $mail)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
            return true;
        }

        // throw new \Exception($query.': '.self::getDb()->error);

        return false;
    }

    public function getUrl()
    {
        $type_url = strtolower(static::$types[$this->type]);
        $slug = sluggify($this->name);
        return "/$type_url/$this->id/$slug";
    }

    public function linkProduct($product)
    {
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

    public function delete()
    {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM
                order_sheets WHERE `sheet_id` = '$id' ";

        return self::getDb()->query($query);
    }
}
