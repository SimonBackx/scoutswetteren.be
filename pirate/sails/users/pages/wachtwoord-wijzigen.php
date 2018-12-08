<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;

class WachtwoordWijzigen extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $errors = array();
        $success = false;

        if (isset($_POST['password'], $_POST['new-password'], $_POST['new-password-repeated'])) {
            $user = User::getUser();

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
                        header("Location: ".User::getRedirectURL());
                    }
                } else {
                    $errors[] = 'Foutief wachtwoord';
                }
            }
        }

        return Template::render('users/wachtwoord-wijzigen', array(
            'errors' => $errors,
            'success' => $success
        ));
    }
}