<?php
namespace Pirate\Sails\Leden\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Validating\Models\Validator;
use Pirate\Wheel\Mail;

class Exporteren extends Page {
    private $layout = false;

    function getStatusCode() {
        return 200;
    }
    function hasOwnLayout() {
        return $this->layout;
    }

    function getContent() {
        $user = Leiding::getUser();
        $scoutsjaar = Inschrijving::getScoutsjaar();
        $selected_scoutsjaar = $scoutsjaar;

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'alle takken');
        $filters = Ouder::$filters;

        $data = array(
            'tak' => $tak,
            'filter' => array_keys($filters)[0],
            'scoutsjaar' => $scoutsjaar
        );

        $allSet = true;
        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allSet = false;
            } else {
                $data[$key] = $_POST[$key];
            }
        }

        $success = false;
        $errors = array();

        if ($allSet) {
            if (!in_array($data['tak'], $takken)) {
                $errors[] = 'Selecteer een tak.';
            }
            if (!isset($filters[$data['filter']])) {
                $errors[] = 'Selecteer een filter.';
            }

            $selected_scoutsjaar = intval($data['scoutsjaar']);
            if ($selected_scoutsjaar == 0) {
                $errors[] = 'Ongeldig scoutsjaar.';
            }
          
            if (count($errors) == 0) {

                if ($data['tak'] == 'alle takken') {
                    $ouders = Ouder::getOuders($data['filter'], null, false, $selected_scoutsjaar);
                    $leden = Ouder::getOuders($data['filter'], null, true, $selected_scoutsjaar);
                } else {
                    $ouders = Ouder::getOuders($data['filter'], $data['tak'], false, $selected_scoutsjaar);
                    $leden = Ouder::getOuders($data['filter'], $data['tak'], true, $selected_scoutsjaar);
                }

                foreach ($leden as $lid) {
                    foreach ($ouders as $ouder) {
                        if ($ouder->gezin->id == $lid->gezin->id) {
                            $lid->ouders[] = $ouder;
                        }
                    }
                }

                if (count($leden) == 0) {
                    $errors[] = 'Er werden geen leden gevonden die aan de criteria voldoen.';
                }  else {
                    $success = true;
                    $this->layout = true;
                    $file = Template::render('admin/leden/exporteren_ledenlijst', array(
                        'leden' => $leden
                    ));
                    $file = "sep=;\n".mb_convert_encoding($file, 'UTF-16LE', 'UTF-8');
                    
                    if (function_exists('mb_strlen')) {
                        $size = mb_strlen($file, '8bit');
                    } else {
                        $size = strlen($file);
                    }

                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream; charset=UTF-16LE');
                    header('Content-Disposition: attachment; filename="'.$data['tak'].'.csv"'); 
                    //header("Content-Length: ".$size);
                    echo $file;
                    exit(1);
                }

            }
        }



        return Template::render('admin/leden/exporteren', array(
            'takken' => $takken,
            'filters' => $filters,
            'errors' => $errors,
            'data' => $data,
            'scoutsjaar' => $scoutsjaar,
            'success' => $success
        ));
    }
}