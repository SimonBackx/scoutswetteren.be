<?php
namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leden\Ouder;

class LedenRouter extends Route {
    function doMatch($url, $parts) {
        if ($url == 'inschrijven') {
            return true;
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'nieuw-lid') {
            return true;
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {
            // Beveiligde sectie
            if (count($parts) == 3 && ($parts[1] == 'account-aanmaken' || $parts[1] == 'wachtwoord-herstellen')) {
                // Key controleren en tijdelijk inloggen
                if (Ouder::temporaryLoginWithPasswordKey($parts[2])) {
                    return true;
                }
                return false;
            }

            // Beveiligde sectie
            if (!Ouder::isLoggedIn()) {
                return false;
            }
        }


        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'inschrijven') {
            require(__DIR__.'/pages/overview.php');
            return new Pages\Overview();
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {
            // Beveiligde sectie: reeds authenticatie gedaan
            if (count($parts) == 3 && ($parts[1] == 'account-aanmaken' || $parts[1] == 'wachtwoord-herstellen')) {
                require(__DIR__.'/pages/set-password.php');
                return new Pages\SetPassword();
            }
        }


        require(__DIR__.'/pages/nieuw-lid.php');
        return new Pages\NieuwLid();
    }
}