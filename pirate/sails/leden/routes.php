<?php
namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;

class LedenRouter extends Route {

    function doMatch($url, $parts) {
        if ($url == 'inschrijven') {
            return true;
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'nieuw-lid') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'inschrijven') {
            require(__DIR__.'/pages/overview.php');
            return new Pages\Overview();
        }
        require(__DIR__.'/pages/nieuw-lid.php');
        return new Pages\NieuwLid();
    }
}