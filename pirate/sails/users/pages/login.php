<?php
namespace Pirate\Sails\Users\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Users\Models\User;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leiding\Models\Leiding;

class Login extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $email = '';
        $wrong = false;
        $success = false;
        $redirect_to = false;
        $redirect_to_url = '';

        if (isset($_POST['email'], $_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            if (User::login($email, $password)) {
                // Doe iets :p
                $success = true;
                if (isset($_POST['redirect_to'])) {
                    header("Location: https://".$_SERVER['SERVER_NAME'].$_POST['redirect_to']);
                } else {
                    header("Location: ".User::getRedirectURL());
                }
                
            } else {
                $wrong = true;
            }
        } else {
            if ($_SERVER['REQUEST_URI'] != '/gebruikers/login' && $_SERVER['REQUEST_URI'] != '/gebruikers/login/') {
                $redirect_to = true;
                $redirect_to_url = $_SERVER['REQUEST_URI'];
            }
        }

        return Template::render('pages/users/login', array(
            'wrong' => $wrong,
            'success' => $success,
            'email' => $email,
            'redirect_to' => $redirect_to,
            'redirect_to_url' => $redirect_to_url
        ));
    }
}