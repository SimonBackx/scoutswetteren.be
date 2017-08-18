<?php
namespace Pirate\Sail\Leden;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Afrekening;
use Pirate\Model\Leden\Inschrijving;

class LedenRouter extends Route {
    private $lid = null;
    private $ouder = null;
    private $afrekening = null;
    private $magicLink = false;

    function doMatch($url, $parts) {
        if ($url == 'inschrijven' && !Ouder::isLoggedIn()) {
            return true;
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'nieuw-lid' && !Ouder::isLoggedIn()) {
            return true;
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {
            // Onbeveiligde sectie
            if (count($parts) == 3 && ($parts[1] == 'account-aanmaken' || $parts[1] == 'wachtwoord-vergeten')) {
                // Key controleren en tijdelijk inloggen
                if (Ouder::temporaryLoginWithPasswordKey($parts[2])) {
                    return true;
                }
                return false;
            }



            // Onbeveiligde sectie
            if (!Ouder::isLoggedIn()) {
                // magic token
                if (count($parts) == 4 && ($parts[1] == 'login')) {

                    $this->magicLink = true;
                    // magic token controleren en inloggen
                    if (Ouder::loginWithMagicToken($parts[2], $parts[3])) {
                        return true;
                    } else {
                        // ongeldig -> doorverwijzen naar andere pagina
                        return true;
                    }
                    return false;
                }

                if (count($parts) == 1) {
                    return true;
                }
                // Volgende paigna's geven altijd een login scherm (daarna overgaan op beveiligde sectie)

                if (count($parts) == 2 && $parts[1] == 'login') {
                    return true;
                }

                if (count($parts) == 3 && $parts[1] == 'afrekening') {
                    return true;
                }

                // Wachtwoord-vergeten zonder loginkey (dus e-mailadres vragen voor key te versturen)
                if (count($parts) == 2 && $parts[1] == 'wachtwoord-vergeten') {
                    return true;
                }

                return false;
            } else {
                // Als magic link -> gewoon doorverwijzen
                if (count($parts) == 4 && ($parts[1] == 'login')) {
                    $this->magicLink = true;
                    return true;
                }
            }

            // Beveiligde sectie
            if (count($parts) == 1) {
                return true;
            }
            if (count($parts) == 2) {
                if ($parts[1] == 'uitloggen') {
                    return true;
                }
                if ($parts[1] == 'broer-zus-toevoegen') {
                    return true;
                }
                if ($parts[1] == 'verleng-inschrijving') {
                    return true;
                }
                if ($parts[1] == 'wachtwoord-wijzigen') {
                    return true;
                }
                if ($parts[1] == 'gezin-nakijken') {
                    return true;
                }
            }

            if (count($parts) == 3) {
                if ($parts[1] == 'ouder-aanpassen') {
                    // kijken of gezin wel in orde is
                    $ouder = Ouder::getOuderForId($parts[2]);
                    if (!is_null($ouder) && $ouder->gezin->id == Ouder::getUser()->gezin->id) {
                        $this->ouder = $ouder;
                        return true;
                    }

                    return false;
                }
                if ($parts[1] == 'lid-aanpassen' || $parts[1] == 'steekkaart') {
                    // kijken of gezin wel in orde is
                    $lid = Lid::getLid($parts[2]);
                    if (!is_null($lid) && $lid->gezin->id == Ouder::getUser()->gezin->id) {
                        $this->lid = $lid;
                        return true;
                    }

                    return false;
                }
                if ($parts[1] == 'afrekening') {
                    // kijken of gezin wel in orde is
                    $afrekening = Afrekening::getAfrekening($parts[2]);
                    if (!is_null($afrekening) && $afrekening->gezin == Ouder::getUser()->gezin->id) {
                        $this->afrekening = $afrekening;
                        return true;
                    }

                    return false;
                }
            }
        }


        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'inschrijven') {
            require(__DIR__.'/pages/overview.php');
            return new Pages\Overview();
        }

        if ($this->magicLink) {
            require(__DIR__.'/pages/magic-link.php');
            return new Pages\MagicLinkPage();
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {

            // Niet ingelogd
             if (!Ouder::isLoggedIn()) {
                if (isset($parts[1]) && $parts[1] == 'wachtwoord-vergeten') {
                    require(__DIR__.'/pages/wachtwoord-vergeten.php');
                    return new Pages\WachtwoordVergeten();
                }

                require(__DIR__.'/pages/login.php');
                return new Pages\Login();
            }

            if (count($parts) == 2) {
                if ($parts[1] == 'uitloggen') {
                    require(__DIR__.'/pages/logout.php');
                    return new Pages\Logout();
                }
                if ($parts[1] == 'wachtwoord-wijzigen') {
                    require(__DIR__.'/pages/wachtwoord-wijzigen.php');
                    return new Pages\WachtwoordWijzigen();
                }

                // Broer zus toevoegen of verlengen enkel in inschrijvingsperiode:
                if (!Inschrijving::isInschrijvingsPeriode()) {
                    require(__DIR__.'/pages/buiten-inschrijvingen-periode.php');
                    return new Pages\BuitenInschrijvingenPeriode();
                }
                if ($parts[1] == 'broer-zus-toevoegen') {
                    require(__DIR__.'/pages/broer-zus-toevoegen.php');
                    return new Pages\BroerZusToevoegen();
                }
                if ($parts[1] == 'verleng-inschrijving') {
                    require(__DIR__.'/pages/verleng-inschrijving.php');
                    return new Pages\VerlengInschrijving();
                }
                if ($parts[1] == 'gezin-nakijken') {
                    require(__DIR__.'/pages/gezin-nakijken.php');
                    return new Pages\GezinNakijken();
                }
            }

            // Beveiligde sectie: reeds authenticatie gedaan
            if (count($parts) == 3) {
                if ($parts[1] == 'ouder-aanpassen' && !empty($this->ouder)) {
                    require(__DIR__.'/pages/ouder-aanpassen.php');
                    return new Pages\OuderAanpassen($this->ouder);
                }

                if ($parts[1] == 'lid-aanpassen' && !empty($this->lid)) {
                    require(__DIR__.'/pages/broer-zus-toevoegen.php');
                    return new Pages\BroerZusToevoegen($this->lid);
                }

                if ($parts[1] == 'steekkaart' && !empty($this->lid)) {
                    require(__DIR__.'/pages/steekkaart.php');
                    return new Pages\EditSteekkaart($this->lid);
                }
                if ($parts[1] == 'afrekening' && !empty($this->afrekening)) {
                    require(__DIR__.'/pages/afrekening.php');
                    return new Pages\ViewAfrekening($this->afrekening);
                }

                if ($parts[1] == 'account-aanmaken' || $parts[1] == 'wachtwoord-vergeten') {
                    require(__DIR__.'/pages/set-password.php');
                    return new Pages\SetPassword();
                }
            }

            if (count($parts) == 1) {
                require(__DIR__.'/pages/ouder-overview.php');
                return new Pages\OuderOverview();
            }
        }

        if (!Inschrijving::isInschrijvingsPeriode()) {
            require(__DIR__.'/pages/buiten-inschrijvingen-periode.php');
            return new Pages\BuitenInschrijvingenPeriode();
        }

        require(__DIR__.'/pages/nieuw-lid.php');
        return new Pages\NieuwLid();
    }
}