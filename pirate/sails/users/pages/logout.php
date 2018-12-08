<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;

class Logout extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        User::logout();
        header("Location: https://".$_SERVER['SERVER_NAME']."/");

        return Template::render('users/logout', array(
            'description' => 'Uitloggen'
        ));
    }
}