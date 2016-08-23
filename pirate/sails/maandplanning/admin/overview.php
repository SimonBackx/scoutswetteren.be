<?php
namespace Pirate\Sail\Maandplanning\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Overview extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return 'todo!';

        /*return Template::render('leiding/login', array(
            'wrong' => $wrong,
            'success' => $success,
            'email' => $email,
            'description' => 'Inlogpagina voor medewerkers'
        ));*/
    }
}