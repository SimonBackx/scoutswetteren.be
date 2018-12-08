<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class SetPassword extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (!User::isLoggedIn()) {
            return 'Error!';
        }
        $user = User::getUser();

        $errors = array();
        $success = false;

        if (isset($_POST['password'], $_POST['password-repeated'])) {
            if ($_POST['password'] != $_POST['password-repeated']) {
                $errors[] = 'Wachtwoorden komen niet overeen, probeer het opnieuw.';
            } else {
                if (strlen($_POST['password']) < 8) {
                    $errors[] = 'Wachtwoord moet minimum 8 lang zijn.';
                } else {
                    if (!$user->changePassword($_POST['password'])) {
                        $errors[] = 'Er ging iets mis. Contacteer de webmaster';
                    } else {
                        $success = true;
                        header("Location: ".User::getRedirectURL());
                        return "Doorverwijzen naar ".User::getRedirectURL();
                    }
                }
            }
        }

        return Template::render('users/set-password', array(
            'new' => (!$user->hasPassword()),
            'success' => $success,
            'errors' => $errors,
            'user' => $user
        ));
    }
}