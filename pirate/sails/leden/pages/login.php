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

        if (isset($_POST['email'], $_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            if (Ouder::login($email, $password)) {
                // Doe iets :p
                $success = true;
                header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                
            } else {
                $wrong = true;
            }
        }

        return Template::render('leden/login', array(
            'wrong' => $wrong,
            'success' => $success,
            'email' => $email
        ));
    }
}