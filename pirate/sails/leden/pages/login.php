<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;

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
            if (Ouder::login($email, $password)) {
                // Doe iets :p
                $success = true;
                if (isset($_POST['redirect_to'])) {
                    header("Location: https://".$_SERVER['SERVER_NAME'].$_POST['redirect_to']);
                } else {
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                }
                
            } else {
                $wrong = true;
            }
        } else {
            if ($_SERVER['REQUEST_URI'] != '/ouders/login' && $_SERVER['REQUEST_URI'] != '/ouders/login/') {
                $redirect_to = true;
                $redirect_to_url = $_SERVER['REQUEST_URI'];
            }
        }

        return Template::render('leden/login', array(
            'wrong' => $wrong,
            'success' => $success,
            'email' => $email,
            'redirect_to' => $redirect_to,
            'redirect_to_url' => $redirect_to_url
        ));
    }
}