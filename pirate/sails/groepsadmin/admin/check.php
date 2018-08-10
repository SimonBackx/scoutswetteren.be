<?php
namespace Pirate\Sail\Groepsadmin\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Groepsadmin\Groepsadmin;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;

class Check extends Page {
    function __construct() {
    }
    function getStatusCode() {
        return 200;
    }

    function clean_special_chars ($s, $d=false) {
        if($d) $s = utf8_decode( $s );

        $chars = array(
            '_' => '/`|´|\^|~|¨|ª|º|©|®/',
            'a' => '/à|á|ả|ạ|ã|â|ầ|ấ|ẩ|ậ|ẫ|ă|ằ|ắ|ẳ|ặ|ẵ|ä|å|æ/',
            'd' => '/đ/',
            'e' => '/è|é|ẻ|ẹ|ẽ|ê|ề|ế|ể|ệ|ễ|ë/',
            'i' => '/ì|í|ỉ|ị|ĩ|î|ï/',
            'o' => '/ò|ó|ỏ|ọ|õ|ô|ồ|ố|ổ|ộ|ỗ|ö|ø/',
            'u' => '/ù|ú|û|ũ|ü|ů|ủ|ụ|ư|ứ|ừ|ữ|ử|ự/',
            'A' => '/À|Á|Ả|Ạ|Ã|Â|Ầ|Ấ|Ẩ|Ậ|Ẫ|Ă|Ằ|Ắ|Ẳ|Ặ|Ẵ|Ä|Å|Æ/',
            'D' => '/Đ/',
            'E' => '/È|É|Ẻ|Ẹ|Ẽ|Ê|Ề|Ế|Ể|Ệ|Ễ|Ê|Ë/',
            'I' => '/Ì|Í|Ỉ|Ị|Ĩ|Î|Ï/',
            'O' => '/Ò|Ó|Ỏ|Ọ|Õ|Ô|Ồ|Ố|Ổ|Ộ|Ỗ|Ö|Ø/',
            'U' => '/Ù|Ú|Û|Ũ|Ü|Ů|Ủ|Ụ|Ư|Ứ|Ừ|Ữ|Ử|Ự/',
            'c' => '/ć|ĉ|ç/',
            'C' => '/Ć|Ĉ|Ç/',
            'n' => '/ñ/',
            'N' => '/Ñ/',
            'y' => '/ý|ỳ|ỷ|ỵ|ỹ|ŷ|ÿ/',
            'Y' => '/Ý|Ỳ|Ỷ|Ỵ|Ỹ|Ŷ|Ÿ/'
        );

        return preg_replace("/[^A-Za-z0-9]/", '', strtolower(trim( preg_replace( $chars, array_keys( $chars ), $s ) )));
    }


    function getContent() {
        $groepsadmin = new Groepsadmin();
        if ($groepsadmin->login()) {
            if ($groepsadmin->getLedenlijst()) {
                // Leden ophalen
                $leden = Lid::getLedenFull();
                $ledenlijst = $groepsadmin->ledenlijst;

                $niet_aanwezig_in_groepsadmin = array();
                $niet_aanwezig_op_website = array();

                $not_equal_leden = [];

                foreach ($leden as $lid) {
                    $found = false;
                    foreach ($ledenlijst as $groepadminLid) {
                        if (!$groepadminLid->found && $groepadminLid->isEqual($lid)) {
                            $groepadminLid->markFound($lid);
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $not_equal_leden[] = $lid;
                        $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Not found', 'geboortedatum' => '/');
                    }
                }

                $not_found = [];

                foreach ($not_equal_leden as $lid) {
                    $found = false;
                    foreach ($ledenlijst as $groepadminLid) {
                        if (!$groepadminLid->found && $groepadminLid->isProbablyEqual($lid)) {
                            $groepadminLid->markFound($lid);
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Toch nog gevonden', 'geboortedatum' => '/');
                    } else {
                        $not_found[] = $lid;
                    }
                }

                if (count($not_found) > 0) {
                    if (!$groepsadmin->getOudLedenlijst()) {
                        return "Error getting old ledenlijst";
                    }

                    $oud_ledenlijst = $groepsadmin->ledenlijst;
    
                    foreach ($not_found as $lid) {
                        $found = false;
                        foreach ($oud_ledenlijst as $groepadminLid) {
                            if (!$groepadminLid->found && $groepadminLid->isEqual($lid)) {
                                $groepadminLid->markFound($lid);
                                $found = true;
                                break;
                            }
                        }
                        
                        if ($found) {
                            $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Toch nog gevonden in oud leden', 'geboortedatum' => '/');
                        }
                    }
                }
                

                foreach ($ledenlijst as $groepadminLid) {
                    if (!$groepadminLid->found) {
                        $niet_aanwezig_op_website[] = array('lid' => $groepadminLid, 'info' => 'Niet ingeschreven op website');
                    } else {
                        if ($groepadminLid->needsSync()) {
                            $niet_aanwezig_op_website[] = array('lid' => $groepadminLid, 'info' => 'Moet gesynct worden');
                            $groepadminLid->sync($groepsadmin);
                        }
                    }
                }


                return Template::render('leden/admin/groepsadmin-check', array(
                    'success' => true,
                    'error' => '',
                    'niet_aanwezig_in_groepsadmin' => $niet_aanwezig_in_groepsadmin,
                    'niet_aanwezig_op_website' => $niet_aanwezig_op_website
                ));


                // Welke leden staan niet in groepsadmin?
                /*$niet_aanwezig_in_groepsadmin = array();

                foreach ($leden as $lid) {
                    $found = false;
                    $naam_wel_gevonden = null;
                    $bevat_schrijffouten = null;

                    $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');

                    foreach ($ledenlijst as $groepsadmin_lid) {
                        if ($this->clean_special_chars($groepsadmin_lid->voornaam) == $this->clean_special_chars($lid->voornaam) &&
                            $this->clean_special_chars($groepsadmin_lid->achternaam) == $this->clean_special_chars($lid->achternaam)
                            ) {
                            $naam_wel_gevonden = $groepsadmin_lid;

                            if ($groepsadmin_lid->geboortedatum == $geboortedatum_string) {
                                if ($groepsadmin_lid->voornaam != $lid->voornaam || $groepsadmin_lid->achternaam != $lid->achternaam ) {
                                    $bevat_schrijffouten = $groepsadmin_lid;
                                }

                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found) {
                        if (isset($naam_wel_gevonden)) {
                            $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Niet gevonden, wel met andere geboortedatum: '.$naam_wel_gevonden->geboortedatum.', fout of ander lid?', 'geboortedatum' => $geboortedatum_string);
                        } else {
                            $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Niet gevonden in groepsadmin', 'geboortedatum' => $geboortedatum_string);
                        }
                    } else {
                        if (isset($bevat_schrijffouten)) {
                            $niet_aanwezig_in_groepsadmin[] = array('lid' => $lid, 'info' => 'Bevat schrijffouten <-> '.$bevat_schrijffouten->voornaam.' '.$bevat_schrijffouten->achternaam, 'geboortedatum' => $geboortedatum_string);
                        }
                    }
                }

                $niet_aanwezig_op_website = array();

                foreach ($ledenlijst as $groepsadmin_lid) {
                    $found = false;
                    $naam_wel_gevonden = null;

                    foreach ($leden as $lid) {
                        $geboortedatum_string = $lid->geboortedatum->format('d/m/Y');

                        if ($this->clean_special_chars($groepsadmin_lid->voornaam) == $this->clean_special_chars($lid->voornaam) &&
                            $this->clean_special_chars($groepsadmin_lid->achternaam) == $this->clean_special_chars($lid->achternaam)
                            ) {
                            $naam_wel_gevonden = $lid;
                            if ($groepsadmin_lid->geboortedatum == $geboortedatum_string) {
                                $found = true;
                                break;
                            }
                        }
                    }

                    if (!$found) {
                        if (isset($naam_wel_gevonden)) {
                            $niet_aanwezig_op_website[] = array('lid' => $groepsadmin_lid, 'info' => 'Geboortedatum ergens fout (zie hierboven)');
                        } else {
                            $niet_aanwezig_op_website[] = array('lid' => $groepsadmin_lid, 'info' => '');
                        }
                    }
                }

                
                // Welke leden staan te veel in groepsadmin?

                return Template::render('leden/admin/groepsadmin-check', array(
                    'success' => true,
                    'error' => '',
                    'niet_aanwezig_in_groepsadmin' => $niet_aanwezig_in_groepsadmin,
                    'niet_aanwezig_op_website' => $niet_aanwezig_op_website
                ));*/

            } else {
                return Template::render('leden/admin/groepsadmin-check', array(
                    'success' => false,
                    'error' => 'Kon ledenlijst niet ophalen'
                ));
            }
        }

        return Template::render('leden/admin/groepsadmin-check', array(
            'success' => false,
            'error' => 'Kon ledenlijst niet ophalen (inloggen mislukt)'
        ));
    }
}