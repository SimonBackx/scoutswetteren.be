<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;
use Pirate\Mail\Mail;

class WachtwoordVergeten extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $errors = array();
        $success = false;
        $email = "";

        if (isset($_POST['email'])) {
            $email = $_POST['email'];
            $ouder = Ouder::getOuderForEmail($email);


            if (!isset($ouder)) {
                $errors[] = "Het opgegeven e-mailadres staat niet geregistreerd in ons systeem. Gebruik het e-mailadres waarop je e-mails van ons ontvangt.";
            } else {
                if ($ouder->generatePasswordRecoveryKey()) {
                    // Mail versturen enzo
                    // TODO
                    $mail = new Mail(
                        'Wachtwoord opnieuw instellen - Scouts Prins Boudewijn', 
                        'wachtwoord-ouders', 
                        array('url' => $ouder->getSetPasswordUrl(), 'naam' => $ouder->voornaam)
                    );

                    $mail->addTo(
                        $ouder->email, 
                        array(),
                        $ouder->voornaam.' '.$ouder->achternaam
                    );

                    if ($mail->send()) {
                        $success = true;
                    } else {
                        $errors[] = "Er ging iets mis bij het versturen van de e-mail. Neem contact met ons op als dit zich blijft voordoen.";
                    }

                } else {
                    $errors[] = "Er ging iets mis bij het aanmaken van de e-mail. Neem contact met ons op.";
                }
                
            }

        }

        return Template::render('leden/wachtwoord-vergeten', array(
            'errors' => $errors,
            'email' => $email,
            'success' => $success
        ));
    }
}