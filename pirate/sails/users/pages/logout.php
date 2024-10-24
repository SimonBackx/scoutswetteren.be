<?php
namespace Pirate\Sails\Users\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Users\Models\User;

class Logout extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        User::logout();
        header("Location: https://".$_SERVER['SERVER_NAME']."/");

        return Template::render('pages/users/logout', array(
            'description' => 'Uitloggen'
        ));
    }
}