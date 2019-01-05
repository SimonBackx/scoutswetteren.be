<?php
namespace Pirate\Sail\Webshop;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Webshop\OrderSheet;

class WebshopRouter extends Route {
    function doMatch($url, $parts) {
        /*if ($match = $this->match($parts, '/gebruikers/wachtwoord-kiezen/@key', ['key' => 'string'])) {        
            if (User::temporaryLoginWithPasswordKey($match->params->key)) {        
                $this->setPage(new Pages\SetPassword());
                return true;
            }
            return false;
        }*/

        if ($result = $this->match($parts, '/inschrijvingen/@id/@slug', ['id' => 'string', 'slug' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Pages\OrderSheet($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/bestellingen/@id/@slug', ['id' => 'string', 'slug' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Pages\OrderSheet($order_sheet));
            return true;
        }

        return false;
    }
}