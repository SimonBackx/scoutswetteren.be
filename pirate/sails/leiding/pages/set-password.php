<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

use Pirate\Database\Database;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class SetPassword extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (!Leiding::isLoggedIn()) {
            return;
        }

        $leiding = Leiding::getUser();

        $errors = array();
        $success = false;

        $data = array(
            'phone' => $leiding->user->phone,
            'mail' => $leiding->user->mail,
            'totem' => $leiding->totem
        );
        $allset = true;

        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                $allset = false;
            } else {
                $data[$key] = $_POST[$key];
            }
        }

        if ($allset && isset($_POST['password'], $_POST['password-repeated'])) {
            $errors = $leiding->setProperties($data);

            if (count($errors) == 0) {
                if (!$leiding->save()) {
                     $errors[] = 'Er ging iets mis bij het opslaan';
                } elseif ($_POST['password'] != $_POST['password-repeated']) {
                    $errors[] = 'Wachtwoorden komen niet overeen, probeer het opnieuw.';
                } else {
                    if (strlen($_POST['password']) < 8) {
                        $errors[] = 'Wachtwoord moet minimum 8 lang zijn.';
                    } else {
                        if (!$leiding->changePassword($_POST['password'])) {
                            $errors[] = 'Er ging iets mis. Contacteer de webmaster';
                        } else {
                            $success = true;
                            header("Location: https://".$_SERVER['SERVER_NAME']."/admin");
                            return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/admin";
                        }
                    }
                }
            }
        }


        return Template::render('leiding/set-password', array(
            'new' => (!$leiding->hasPassword()),
            'success' => $success,
            'errors' => $errors,
            'leiding' => $leiding,
            'data' => $data
        ));
    }
}