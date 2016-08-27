<?php
namespace Pirate\Model\Validating;
use Pirate\Model\Model;

class Validator extends Model {
    static function isValidFirstname($firstname) {
        $pattern = '/^[\w ]+$/';
        return (preg_match($pattern, $firstname) === 1);
    }

    static function isValidLastname($lastname) {
        $pattern = '/^[\w ]+$/';
        return (preg_match($pattern, $lastname) === 1);
    }

    static function isValidTotem($totem) {
        $pattern = '/^[\w ]+$/';
        return (preg_match($pattern, $totem) === 1);
    }

    static function isValidPhone($phone) {
        $pattern = '/^[0-9  +()]+$/';
        return (preg_match($pattern, $phone) === 1);
    }

    static function isValidMail($mail) {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL));
    }

    // Returns true on success
    static function validatePhone(&$in, &$out, &$errors) {
        if (!self::isValidPhone($in)) {
            $errors[] = 'Ongeldig GSM nummer';
            return false;
        }

        $output = preg_replace('/[^0-9+]/', '', $in);

        // lengte bepalen
        if (strlen($output) != 10 && strlen($output) != 12 && strlen($output) != 13) {
            $errors[] = 'Vul GSM nummer in formaat +32 4XX XX XX XX of 04XX XX XX XX';
        } else {
            $original = $output;
            $error = false;
            if (substr($output, 0, 4) == '0032' && strlen($output) == 13) {
                $output = substr($output, 2);
            }

            if (substr($output, 0, 3) == '+32' && strlen($output) == 12) {
                $output = substr($output, 1);
            }

            if (substr($output, 0, 3) != '324' || (strlen($original) != 12 && strlen($original) != 13)) {
                if (substr($output, 0, 2) != '04') {
                    $errors[] = 'Vul GSM nummer in formaat +32 4XX XX XX XX of 04XX XX XX XX';
                    $error = true;
                } else {
                    $output = '32'.substr($output, 1);
                }
            }

            if (!$error) {
                $output = '+'.$output;

                // Non breaking spaces toevoegen
                $strlen = strlen( $output );
                $result = '';
                for( $i = 0; $i < $strlen; $i++ ) {
                    $char = substr( $output, $i, 1 );
                    if ($i == 3 || ($i >= 5 && $i%2 == 0)) {
                        $result .= ' ';
                    }
                    $result .= $char;
                }

                $out = $result;
                $in = $out;
                return true;
            }

        }
        return false;
    }
}