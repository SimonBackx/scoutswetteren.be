<?php
namespace Pirate\Sail\Contact\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leiding\Leiding;
use Pirate\Mail\Mail;
use Pirate\Model\Leden\Ouder;

class Contact extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $data = array(
            'wie' => '',
            'name' => '',
            'email' => '',
            'subject' => '',
            'message' => '',
        );

        $wie = Leiding::getContacts();

        $success = false;
        $errors = array();

        $allSet = true;
        foreach ($data as $key => $value) {
            if (isset($_POST[$key])) {
                $data[$key] = $_POST[$key];
            } else {
                if ($key == 'wie') {
                    continue;
                }
                $allSet = false;
            }
        }

        // Beveiliging tegen robots
        if (isset($_POST['nickname'])) {
            if (strlen($_POST['nickname']) > 0) {
                $allSet = false;
            }
        }

        if ($allSet) {
            $data['subject'] = trim($data['subject']);

            if (!Leiding::getContactEmail($data['wie'], $contactpersoon_email, $contactpersoon_naam, $send_from)) {
                $errors[] = 'Je moet selecteren wie je wilt contacteren.';
            }

            if (!Validator::isValidName($data['name'])) {
                $errors[] = 'Ongeldige naam. Controleer of je geen fouten hebt gemaakt.';
            }
            if (!Validator::isValidMail($data['email'])) {
                $errors[] = 'Ongeldig e-mailadres. Controleer of je geen fouten hebt gemaakt.';
            }
            if (strlen($data['subject']) < 4) {
                $errors[] = 'Onderwerp te kort';
            }
            if (strlen($data['message']) < 30) {
                $errors[] = 'Uw bericht is te kort. Voorzie voldoende informatie.';
            }
            if (count($errors) == 0) {
                $success = true;
                $mail = new Mail('Webformulier: '.$data['subject'], 'contact', array('data' => $data, 'naam' => $contactpersoon_naam));
                
                $mail->setReplyTo($data['email']);
                $mail->addTo(
                    $contactpersoon_email,
                    array(),
                    $contactpersoon_naam
                );

                if (!$mail->send()) {
                    $errors[] = 'Er ging iets mis bij het versturen van de e-mail. Contacteer de gekozen contactpersoon via '.$contactpersoon_email.'.';
                } else {
                    $success = true;
                }
            }
        }

        $scoutsjaar = Inschrijving::getScoutsjaar();
        $takkenverdeling = Lid::getTakkenVerdeling($scoutsjaar);
        $jaar_verdeling = array();
        foreach ($takkenverdeling as $jaar => $tak) {
            if (!isset($jaar_verdeling[$tak])) {
                $jaar_verdeling[$tak] = array();
            }
            $jaar_verdeling[$tak][] = $jaar;
        }

        $verdeling_string = array();
        foreach ($jaar_verdeling as $tak => $jaren) {
            $min = min($jaren);
            $max = max($jaren);
            if ($min == $max) {
                $verdeling_string[$tak] = 'in '.$min;
            } else {
                $verdeling_string[$tak] = 'in het jaar '. $min.' tot '.$max;
            }
        }

        $leiding_data = array();
        if (Leiding::isLoggedIn() || Ouder::isLoggedIn()) {
            $leiding = Leiding::getLeiding('leiding');
            foreach ($leiding as $value) {
                if (!isset($value->tak)) {
                    continue;
                }
                if (!isset($leiding_data[$value->tak])) {
                    $leiding_data[$value->tak] = array();
                }
                $leiding_data[$value->tak][] = $value;
            }

            foreach ($leiding_data as $key => $value) {
                shuffle($leiding_data[$key]);
            }


        }

        return Template::render('contact/contact', array(
            'data' => $data,
            'errors' => $errors,
            'success' => $success,
            'wie' => $wie,
            'takkenverdeling' => $verdeling_string,
            'leiding' => $leiding_data
            )
        );
    }
}