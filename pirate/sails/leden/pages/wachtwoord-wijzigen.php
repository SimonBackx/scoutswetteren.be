<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;

class WachtwoordWijzigen extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $errors = array();
        $success = false;

        if (isset($_POST['password'], $_POST['new-password'], $_POST['new-password-repeated'])) {
            $user = Ouder::getUser();

            $password = $_POST['password'];
            $new_password = $_POST['new-password'];

            if ($new_password != $_POST['new-password-repeated']) {
                $errors[] = 'Opgegeven wachtwoorden komen niet overeen';
            } else {
                if ($user->confirmPassword($password)) {
                    if (!$user->changePassword($new_password)) {
                        $errors[] = 'Controleer of je nieuwe wachtwoord langer is dan 9 tekens.';
                    } else {
                        $success = true;
                        header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    }
                } else {
                    $errors[] = 'Foutief wachtwoord';
                }
            }
        }

        return Template::render('leden/wachtwoord-wijzigen', array(
            'errors' => $errors,
            'success' => $success
        ));
    }
}