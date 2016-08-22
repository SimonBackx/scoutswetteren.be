<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

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
            if (Leiding::login($email, $password)) {
                // Doe iets :p
                $success = true;
                header("Location: https://".$_SERVER['SERVER_NAME']."/");
                
            } else {
                $wrong = true;
            }
        }

        return Template::render('leiding/login', array(
            'fixed_menu' => true,
            'wrong' => $wrong,
            'success' => $success,
            'email' => $email,
            'description' => 'Inlogpagina voor medewerkers'
        ));
    }
}