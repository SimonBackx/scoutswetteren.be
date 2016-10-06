<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Validating\Validator;
use Pirate\Mail\Mail;

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

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'alle takken');
        $filters = Ouder::$filters;

        $data = array(
            'tak' => $tak,
            'filter' => array_keys($filters)[0],
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
          
            if (count($errors) == 0) {

                if ($data['tak'] == 'alle takken') {
                    $ouders = Ouder::getOuders($data['filter']);
                    $leden = Lid::getLeden($data['filter']);
                } else {
                    $ouders = Ouder::getOuders($data['filter'], $data['tak']);
                    $leden = Lid::getLeden($data['filter'], $data['tak']);
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
                    $file = Template::render('leden/admin/exporteren_ledenlijst', array(
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




        return Template::render('leden/admin/exporteren', array(
            'takken' => $takken,
            'filters' => $filters,
            'errors' => $errors,
            'data' => $data,
            'success' => $success
        ));
    }
}