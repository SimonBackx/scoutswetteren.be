<?php
namespace Pirate\Sail\Webshop;
use Pirate\Page\Page;
use Pirate\Route\Route;

class WebshopRouter extends Route {
    function doMatch($url, $parts) {
        /*if ($match = $this->match($parts, '/gebruikers/wachtwoord-kiezen/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\SetPassword());
                return true;
            }
            return false;
        }*/

        return false;
    }
}