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
use Pirate\Model\Files\File;

class SmsPage extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");

        if (!$iPhone && !$Android) {
            return Template::render('leden/admin/no-sms', array());
        }

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
            'message' => ''
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
        $send_leden = false;
        $send_only_leden = false;

        if ($allSet) {

            $send_leden = (isset($_POST['send_leden']));
            $send_only_leden = $send_leden && (isset($_POST['send_only_leden']));

            if (!in_array($data['tak'], $takken)) {
                $errors[] = 'Selecteer een tak waar je de sms\'en wil naar versturen.';
            }
            if (!isset($filters[$data['filter']])) {
                $errors[] = 'Selecteer een filter.';
            }

            if (count($errors) == 0) {
                //$success = true;
                $numbers = array();

                if (!$send_only_leden) {
                    $ouders = array();

                    if ($data['tak'] == 'alle takken') {
                        $ouders = Ouder::getOuders($data['filter']);
                    } else {
                        $ouders = Ouder::getOuders($data['filter'], $data['tak']);
                    }
                    foreach ($ouders as $ouder) {
                        $stripped = preg_replace('/[ \s]+/', '', $ouder->gsm);
                        $numbers[] = $stripped;
                    }

                }
                if ($send_leden) {
                    $leden = array();

                    if ($data['tak'] == 'alle takken') {
                        $leden = Ouder::getOuders($data['filter'], null, true);
                    } else {
                        $leden = Ouder::getOuders($data['filter'], $data['tak'], true);
                    }

                    foreach ($leden as $lid) {
                        if (isset($lid->gsm)) {
                            $stripped = preg_replace('/[ \s]+/', '', $lid->gsm);
                            $numbers[] = $stripped;
                        }
                    }
                }

                // Duplicates verwijderen
                $numbers = array_unique($numbers);

                if (count($numbers) == 0) {
                    $errors[] = 'Er zijn geen leden die aan de criteria voldoen.';
                }  else {
                    if ($Android) {
                        $url = "sms:";
                    } else {
                        $url = "sms:/open?addresses=";
                    }
                    $first = true;
                    foreach ($numbers as $number) {
                        if (!$first) {
                            $url .= ",";
                        }
                        $first = false;
                        $url .= $number;
                    }

                    if (strlen($data['message']) > 0) {
                        if ($Android) {
                            $url .= "?body=".rawurlencode($data['message']);
                        } else {
                            $url .= "&body=".rawurlencode($data['message']);
                        }
                    }
                    

                    // Body instellen
                    // Android: <a href="sms:/* phone number here */?body=/* body text here */">Link</a>
                    // ios7: <a href="sms:/* phone number here */;body=/* body text here */">Link</a>
                    // ioS8: <a href="sms:/* phone number here */&body=/* body text here */">Link</a>

                    @header('Location: '.$url);
                    $success = true;
                }

            }
        }


        return Template::render('leden/admin/sms', array(
            'takken' => $takken,
            'filters' => $filters,
            'errors' => $errors,
            'data' => $data,
            'success' => $success,
            'send_leden' => $send_leden,
            'send_only_leden' => $send_only_leden
        ));
    }
}