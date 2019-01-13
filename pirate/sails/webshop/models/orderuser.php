<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Users\User;


use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

class OrderUser extends Model implements \JsonSerializable {
    public $id;
    public $user; // nullable object
    public $firstname;
    public $lastname;
    public $mail;
    public $phone;

    function __construct($row = null) {
        if (is_null($row)) {
            return;
        }
        $this->id = $row['order_user_id'];
        $this->user = isset($row['user_id']) ? new User($row) : null;
        $this->firstname = $row['order_user_firstname'];
        $this->lastname = $row['order_user_lastname'];
        $this->mail = $row['order_user_mail'];
        $this->phone = $row['order_user_phone'];
    }

    function jsonSerialize() {
        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'mail' => $this->mail,
            'phone' => $this->phone,
        ];
    }

    /// Set the properties of this model. Throws an error if the data is not valid
    function setProperties(&$data) {
        $errors = new ValidationErrors();
        $list_errors = [];

        if (isset($data['id'])) {
            $user = User::getById($data['id']);
            if (!isset($user)) {
                $errors->extend(new ValidationError('Ongeldige gebruiker', 'id'));
            } else {
                if (User::isLoggedIn() && User::getUser()->id != $user->id) {
                    $errors->extend(new ValidationError('Je bent tijdens het bestellen uitgelogd geraakt. Herlaad de pagina en probeer opnieuw.', 'user_id'));
                } else {
                    $this->user = $user;
                    $this->firstname = $user->firstname;
                    $this->lastname = $user->lastname;
                    $this->mail = $user->mail;
                    $this->phone = $user->phone;
                }
            }
        } else {
            if (isset($data['firstname'], $data['lastname'])) {
                if (Validator::isValidFirstname($data['firstname'])) {
                    $this->firstname = ucwords(mb_strtolower(trim($data['firstname'])));
                    $data['firstname'] = $this->firstname;
                } else {
                    $errors->extend(new ValidationError('Ongeldige voornaam', 'firstname'));
                }
    
                if (Validator::isValidLastname($data['lastname'])) {
                    $this->lastname = ucwords(mb_strtolower(trim($data['lastname'])));
                    $data['lastname'] = $this->lastname;
                } else {
                    $errors->extend(new ValidationError('Ongeldige achternaam', 'lastname'));
                }
            } else {
                $errors->extend(new ValidationError('Geen naam opgegeven', 'firstname'));
            }
    
            if (isset($data['mail'])) {
                if (Validator::isValidMail($data['mail'])) {
                    $this->mail = strtolower($data['mail']);
                } else {
                    $errors->extend(new ValidationError('Ongeldig e-mailadres'));
                }
            } else {
                $errors->extend(new ValidationError('Geen e-mailadres opgegeven', 'mail'));
            }
    
            // Als admin een user aanpast hoeft hij geen telefoon nummer op te geven
            // Anders moet hij wel altijd een telefoonnummer opgeven
            if (isset($data['phone']) && strlen($data['phone']) > 0) {
                Validator::validatePhone($data['phone'], $this->phone, $list_errors);
            } else {
                $errors->extend(new ValidationError('Geen GSM-nummer opgegeven', 'phone'));
            }
        }


        foreach ($list_errors as $message) {
            $errors->extend(new ValidationError($message));
        }

        if (count($errors->getErrors()) > 0) {
            throw $errors;
        }
    }

    function save(){
        $firstname = self::getDb()->escape_string($this->firstname);
        $lastname = self::getDb()->escape_string($this->lastname);

        if (!isset($this->phone)) {
            $phone = 'NULL';
        } else {
            $phone = "'".self::getDb()->escape_string($this->phone)."'";
        }

        if (!isset($this->mail)) {
            $mail = 'NULL';
        } else {
            $mail = "'".self::getDb()->escape_string($this->mail)."'";
        }

        if (!isset($this->user->id)) {
            $user = 'NULL';
        } else {
            $user = "'".self::getDb()->escape_string($this->user->id)."'";
        }

        // Permissions
        if (isset($this->id)) {
            $id = self::getDb()->escape_string($this->id);

            $query = "UPDATE order_users 
                SET 
                order_user_firstname = '$firstname',
                order_user_lastname = '$lastname',
                order_user_mail = $mail,
                order_user_phone = $phone,
                order_user_user = $user
                 where `order_user_id` = '$id' 
            ";
        } else {
            $query = "INSERT INTO 
                order_users (`order_user_firstname`, `order_user_lastname`, `order_user_mail`, `order_user_phone`, `order_user_user`)
                VALUES ('$firstname', '$lastname', $mail, $phone,  $user)";
        }

        $result = self::getDb()->query($query);

        if ($result) {
            $new = false;
            if (!isset($this->id)) {
                $this->id = self::getDb()->insert_id;
            }
        }

        return $result;
    }

    function delete() {
        $id = self::getDb()->escape_string($this->id);
        $query = "DELETE FROM 
                order_users WHERE `order_user_id` = '$id' ";

        return self::getDb()->query($query);
    }
}