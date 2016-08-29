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
        $pattern = '/^[0-9  +().\/]+$/';
        return (preg_match($pattern, $phone) === 1);
    }

    static function isValidMail($mail) {
        return (filter_var($mail, FILTER_VALIDATE_EMAIL));
    }

    static function isValidAddress($adres) {
        $pattern = '/^.* [0-9]+.*?$/';
        return (preg_match($pattern, $adres) === 1);
    }

    static function validateGemeente(&$inGemeente, &$inPostcode, &$outGemeente, &$outPostcode, &$errors) {
        if (empty($inGemeente)) {
            $errors[] = 'Vul een gemeente in.';
            return false;
        }
        if (empty($inPostcode)) {
            $errors[] = 'Vul een postcode in.';
            return false;;
        }
        $inGemeenteEscaped = self::getDb()->escape_string($inGemeente);
        $inPostcodeEscaped = self::getDb()->escape_string($inPostcode);
        $query = "select * from gemeenten where postcode = '$inPostcodeEscaped' or gemeente LIKE '%$inGemeente%' order by (postcode = '$inPostcodeEscaped' and gemeente = '$inGemeenteEscaped') desc, gemeente = '$inGemeenteEscaped' desc";
        if ($result = self::getDb()->query($query)) {
            if ($result->num_rows > 0){
                $row = $result->fetch_assoc();
                if ($row['postcode'] != $inPostcode) {
                    $errors[] = 'Opgegeven postcode voor '.$inGemeente.' niet gevonden, bedoelt u '.$row['postcode'].' ('.$row['provincie'].')?';
                    return false;
                }
                elseif (strtolower($row['gemeente']) != strtolower($inGemeente)) {
                    $errors[] = 'Opgegeven gemeente niet gevonden, bedoelt u '.$row['gemeente'].' ('.$row['provincie'].')?';;
                    return false;
                } else {
                    $inGemeente = $row['gemeente'];
                    $inPostcode = $row['postcode'];

                    $outGemeente = $inGemeente;
                    $outPostcode = $inPostcode;
                    return true;
                }
            }
        }
        $errors[] = 'Geen gemeente gevonden met opgegeven naam en postcode.';
        return false;
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

    static function validateNetPhone(&$in, &$out, &$errors) {
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

        if (strlen($filtered_in) != $needed_length) {
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
}