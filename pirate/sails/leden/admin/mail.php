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

class MailPage extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $user = Leiding::getUser();

        $tak = '';
        if (!empty($user->tak)) {
            $tak = $user->tak;
        } 

        $takken = array('kapoenen', 'wouters', 'jonggivers', 'givers', 'jin', 'alle takken');
        $senders = array();
        $filters = Ouder::$filters;

        $data = array(
            'tak' => $tak,
            'sender' => '',
            'filter' => array_keys($filters)[0],
            'subject' => '',
            'message' => ''
        );

        $senders[] = $user->mail;

        $contacts = Leiding::getContacts();
        foreach ($contacts as $key => $value) {
            if (Leiding::getContactEmail($key, $email, $naam)) {
                $senders[] = $email;
                if ($key == $tak) {
                    $data['sender'] = $email;
                }
            }
        }

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
            if (!in_array($data['sender'], $senders)) {
                $errors[] = 'Selecteer een e-mailadres vanaf waar je de e-mail wil versturen.';
            }
            if (!in_array($data['tak'], $takken)) {
                $errors[] = 'Selecteer een tak waar je de e-mail wil naar versturen.';
            }
            if (!isset($filters[$data['filter']])) {
                $errors[] = 'Selecteer een filter.';
            }
            if (strlen($data['subject']) < 5) {
                $errors[] = 'Het onderwerp is te kort.';
            }
            if (strlen($data['message']) < 40) {
                $errors[] = 'Het bericht is te kort. Gebruik a.u.b. een goede opbouw van je e-mail.';
            }
            
            if (count($errors) == 0) {
                //$success = true;
                $ouders = array();

                if ($data['tak'] == 'alle takken') {
                    $ouders = Ouder::getOuders($data['filter']);
                } else {
                    $ouders = Ouder::getOuders($data['filter'], $data['tak']);
                }

                if (count($ouders) == 0) {
                    $errors[] = 'Er werden geen ouders gevonden die aan de criteria voldoen.';
                }  else {

                    $mail = new Mail($data['subject'], 'mail', array('message' => $data['message']));
                    $mail->setReplyTo($data['sender']);

                    foreach ($ouders as $ouder) {
                        $mail->addTo(
                            $ouder->email, 
                            array('reason' => 'Dit bericht werd naar je verstuurd omdat je geregistreerd staat als ouder van één van onze leden. Je kan het e-mailadres wijzigen door naar onze website te surfen en daar in te loggen als ouder (inschrijven -> inloggen).'),
                            $ouder->voornaam.' '.$ouder->achternaam
                        );
                    }

                    $mail->addTo( $data['sender'] , array('reason' => 'Dit bericht is een kopie van het bericht dat naar ouders ('.$data['tak'].') is verzonden via de website door '.$user->firstname.' '.$user->lastname));

                    $success = $mail->send();

                    if (!$success) {
                        $errors[] = 'Er ging iets mis bij het versturen van de e-mails. Probeer het later opnieuw.';
                    }
                }

            }
        }


        return Template::render('leden/admin/mail', array(
            'takken' => $takken,
            'senders' => $senders,
            'filters' => $filters,
            'errors' => $errors,
            'data' => $data,
            'success' => $success
        ));
    }
}