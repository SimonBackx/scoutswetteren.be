<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Login extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {

        return Template::render('leiding/login', array(
            'fixed_menu' => true,
            'description' => 'Beschrijving',
            'content' => 'inloggen'
        ));
    }
}