<?php
namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Lid;

class LedenRouter extends Route {
    private $lid = null;

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

            if (count($parts) == 1) {
                return true;
            }

            if (count($parts) == 3 && $parts[1] == 'steekkaart') {
                // kijken of gezin wel in orde is
                $lid = Lid::getLid($parts[2]);
                if (!is_null($lid) && $lid->gezin == Ouder::getUser()->gezin) {
                    $this->lid = $lid;
                    return true;
                }

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
            if (count($parts) == 3) {
                if ($parts[1] == 'steekkaart' && !empty($this->lid)) {
                    require(__DIR__.'/pages/steekkaart.php');
                    return new Pages\EditSteekkaart($this->lid);
                }

                if ($parts[1] == 'account-aanmaken' || $parts[1] == 'wachtwoord-herstellen') {
                    require(__DIR__.'/pages/set-password.php');
                    return new Pages\SetPassword();
                }
            }

            if (count($parts) == 1) {
                require(__DIR__.'/pages/ouder-overview.php');
                return new Pages\OuderOverview();
            }
        }

        require(__DIR__.'/pages/nieuw-lid.php');
        return new Pages\NieuwLid();
    }
}