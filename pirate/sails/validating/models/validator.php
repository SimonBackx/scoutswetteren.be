<?php
namespace Pirate\Model\Validating;
use Pirate\Model\Model;

class Validator extends Model {
    static function isValidFirstname($firstname) {
        $pattern = '/^[A-zÀ-ÿ\'\- ]+$/';
        return (preg_match($pattern, $firstname) === 1);
    }

    static function isValidName($firstname) {
        $pattern = '/^[A-zÀ-ÿ\'\- ]+$/';
        return (preg_match($pattern, $firstname) === 1);
    }

    // Bv 54e FOS
    static function isValidGroupName($firstname) {
        $pattern = '/^[A-zÀ-ÿ0-9\'\-,.\& ]+$/';
        return (preg_match($pattern, $firstname) === 1);
    }

    static function isValidLastname($lastname) {
        $pattern = '/^[A-zÀ-ÿ\'\- ]+$/';
        return (preg_match($pattern, $lastname) === 1);
    }

    static function isValidTotem($totem) {
        $pattern = '/^[A-zÀ-ÿ\'\- ]+$/';
        return (preg_match($pattern, $totem) === 1);
    }

    static function isValidPhone($phone) {
        $pattern = '/^[0-9  +().\/\']+$/';
        return (preg_match($pattern, $phone) === 1);
    }

    static function isValidPrice($phone) {
        $pattern = '/^[\-0-9,.€\'   ]+$/';
        if (!(preg_match($pattern, $phone) === 1)) {
            return false;
        }
        return substr_count($phone, ',') <= 1 ;
    }

    static function isValidMail($mail) {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL));
    }

    static function isValidAddress($adres) {
        $pattern = '/^.+[0-9]+.*$/';
        return (preg_match($pattern, $adres) === 1);
    }

    // Returns true on success
    static function validatePhone(&$in, &$out, &$errors, $partial = false) {
        if (!self::isValidPhone($in)) {
            $errors[] = 'Ongeldig GSM nummer';
            return false;
        }

        $output = preg_replace('/[^0-9+]/', '', $in);

        // lengte bepalen
        if (!$partial && (strlen($output) < 10 || strlen($output) == 11)) {
            $errors[] = 'Ongeldig GSM nummer';
        } else {
            $original = $output;
            $error = false;
            $plus = 0;
            if (substr($output, 0, 2) == '00') {
                $output = substr($output, 2);
                $plus = 2;
            } 
            elseif (substr($output, 0, 1) == '+') {
                $output = substr($output, 1);
                $plus = 1;
            }
            $country = substr($output, 0, 3);

            // Indien landcode is opgegeven:
            if ($plus > 0 &&
                (
                    ($country == '324' && (strlen($original) == 11 + $plus || $partial) )
                || ($country == '316' && (strlen($original) == 11 + $plus || $partial))
                || ($country == '336' && (strlen($original) == 11 + $plus || $partial))
                || ($country == '337' && (strlen($original) == 11 + $plus || $partial))
                || ($country == '491' && (strlen($original) == 14 + $plus || strlen($original) == 13 + $plus || strlen($original) == 12 + $plus || strlen($original) == 11 + $plus || $partial))
                )
            ) {

                
            } else {
                // Geen bekende landcode opgegeven
                
                // Automatisch 04 nummer omvormen in een belgisch nummer, anders melding geven
                if (substr($output, 0, 2) != '04') {
                    $errors[] = 'Ongeldig GSM nummer';
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
                    if ($i <= 7 || $strlen <= 12) {
                        if ($i == 3 || ($i >= 5 && $i%2 == 0)) {
                            $result .= ' ';
                        }
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

    static function validateBothPhone(&$in, &$out, &$errors, $partial = false) {
        $in_copy = $in;
        $out_copy = $out;
        $errors_copy = array();

        if (self::validatePhone($in_copy, $out_copy, $errors_copy, $partial)) {
            $in = $in_copy;
            $out = $out_copy;
            return true;
        }

        $in_copy = $in;
        $out_copy = $out;
        $errors_copy = array();

        if (self::validateNetPhone($in_copy, $out_copy, $errors_copy, $partial)) {
            $in = $in_copy;
            $out = $out_copy;
            return true;
        }

        $errors[] = 'Ongeldig telefoonnummer of gsm-nummer (enkel Belgische nummers toegelaten)';
        return false;
    }

    static function validateNetPhone(&$in, &$out, &$errors, $partial = false) {
        // Formaat:
        //   0x xxx xx xx - dialing a big city, such as Brussels, Antwerp, Liège and Ghent.
        //   0xx  xx xx xx
        //   
        //   of
        //   
        //   +32 x xxx xx xx
        //   +32 xx xx xx xx
        //   0032 x xxx xx xx
        //   0032 xx xx xx xx
        
        // Zones worden niet afgedwongen, maar gebruikt voor het formatteren
        // 
        // Alle zones die maar met 2 cijfers aangegeven worden (grotere steden)
        $zones_kort = array('2','3','4', '9');

        // Bron:
        // http://www.bipt.be/public/files/en/474/20140829153659_Belgian_numbering_plan.pdf

        if (!self::isValidPhone($in)) {
            $errors[] = 'Ongeldig telefoonnummer';
            return false;
        }

        // Optionele nullen verwijderen
        
        $filtered_in = preg_replace('/[^0-9+]/', '', str_replace('(0)', '', $in));

        // Toegelaten: lengte = 9 (zonder plus), of lengte 11 (met plusteken), of lengte 12 (zonder plus ,met 2 nullen)
        $needed_length = 9;
        if (substr($filtered_in, 0, 4) == '0032') {
            $needed_length = 12;
        }
        if (substr($filtered_in, 0, 3) == '+32') {
            $needed_length = 11;
        }

        if (strlen($filtered_in) != $needed_length && !$partial) {
            $errors[] = 'Vul geldig Belgisch telefoonnummer in formaat +32 x xxx xx xx, +32 xx xx xx xx, 0x xxx xx xx of 0xx xx xx xx';
        } else {
            $zonenummer = substr($filtered_in, $needed_length - 8, 1);
            $nummer = substr($filtered_in, $needed_length - 8); // enkel de xjes in het formaat

            // Als in 1 van die korte zones, formaat: +32 x xxx xx xx ipv +32 xx xx xx xx
            $offset = -1;
            if (in_array($zonenummer, $zones_kort)) {
                $offset = 1;
            }

            // Non breaking spaces toevoegen
            $strlen = strlen( $nummer );
            $result = '';
            for( $i = 0; $i < $strlen; $i++ ) {
                $char = substr( $nummer, $i, 1 );
                if (($i%2 == 0 && $i > $offset + 1) || $i == $offset) {
                    $result .= ' ';
                }
                $result .= $char;
            }

            $out = '+32 '.$result;
            $in = $out;
            return true;
        }

        return false;
    }

    static function validatePrice(&$in, &$out, &$errors, $allow_negative = false) {
        if (empty($in)) {
            $in = '€ 0,00';
            $out = 0;
            return true;
        }
        if (!self::isValidPrice($in)) {
            $errors[] = 'Ongeldige prijs';
            return false;
        }
        $comma = strrpos($in, ',');
        $point = strrpos($in, '.');
        $separator = ',';
        // Als geen komma, maar wel een punt:
        // Punt gebruiken indien in laatste 2 characters
        if ($comma == false && $point !== false) {
            if ($point >= strlen($in) - 3) {
                $separator = '.';
            }
        }

        // Alles buiten de komma en de getallen laten staan
        $output = preg_replace('/[^0-9'.$separator.'\-]/', '', $in);

        $price = 0;
        $strlen = strlen( $output );
        $comma = -1;
        $sign = 1;

        for( $i = 0; $i < $strlen; $i++ ) {
            $char = substr( $output, $i, 1 );
            if ($char == '-') {
                if ($i != 0) {
                    $errors[] = 'Ongeldige prijs';
                    return false;
                } else {
                    if (!$allow_negative) {
                        $errors[] = 'Negatieve prijzen zijn niet toegelaten';
                        return false;
                    }
                    $sign = -1;
                }
            }
            if ($char != $separator) {
                if ($comma == -1) {
                    $price = $price*10 + intval($char);
                } else {
                    $price += intval($char)/pow(10, $i-$comma);
                }
            } else {
                $comma = $i;
            }
        }

        $out = $price*$sign;

        $in = '€ '.money_format('%!.2n', $out);

        return false;
    }
}