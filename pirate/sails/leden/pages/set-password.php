<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Gezin;
use Pirate\Database\Database;
use Pirate\Mail\Mail;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class SetPassword extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (!Ouder::isLoggedIn()) {
            return 'Error!';
        }
        $ouder = Ouder::getUser();

        $errors = array();
        $success = false;

        if (isset($_POST['password'], $_POST['password-repeated'])) {
            if ($_POST['password'] != $_POST['password-repeated']) {
                $errors[] = 'Wachtwoorden komen niet overeen, probeer het opnieuw.';
            } else {
                if (strlen($_POST['password']) < 10) {
                    $errors[] = 'Wachtwoord moet minimum 10 lang zijn.';
                } else {
                    if (!$ouder->changePassword($_POST['password'])) {
                        $errors[] = 'Er ging iets mis. Contacteer de webmaster';
                    } else {
                        $success = true;
                        header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                        return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                    }
                }
            }
        }


        return Template::render('leden/set-password', array(
            'new' => (!$ouder->hasPassword()),
            'success' => $success,
            'errors' => $errors,
            'ouder' => $ouder
        ));
    }
}