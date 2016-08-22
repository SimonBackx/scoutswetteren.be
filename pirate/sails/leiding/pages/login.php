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
        if (isset($_POST['email'], $_POST['password'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            if (Leiding::login($email, $password)) {
                echo 'gelukt!';
            }
        }

        return Template::render('leiding/login', array(
            'fixed_menu' => true,
            'description' => 'Inlogpagina voor medewerkers'
        ));
    }
}