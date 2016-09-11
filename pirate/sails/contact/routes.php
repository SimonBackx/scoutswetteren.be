<?php
namespace Pirate\Sail\Contact;
use Pirate\Page\Page;
use Pirate\Route\Route;

class ContactRouter extends Route {
    private $adminPage = null;

    function doMatch($url, $parts) {
        if ($url == 'contact') {
            return true;
        }

       
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/pages/contact.php');
        return new Pages\Contact();
    }
}