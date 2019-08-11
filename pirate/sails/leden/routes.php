<?php
namespace Pirate\Sails\Leden;

use Pirate\Sails\Leden\Models\Afrekening;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Users\Models\User;
use Pirate\Wheel\Route;
use Pirate\Sails\Users\Pages\Login;

class LedenRouter extends Route
{
    private $lid = null;
    private $ouder = null;
    private $afrekening = null;
    private $magicLink = false;

    public function doMatch($url, $parts)
    {
        if ($url == 'inschrijven' && !Ouder::isLoggedIn()) {
            return true;
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'nieuw-gezin' && !Ouder::isLoggedIn() && User::isLoggedIn()) {
            return true;
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'uitzondering-toelaten') {
            return true;
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {
            // Onbeveiligde sectie

            // Onbeveiligde sectie
            if (!Ouder::isLoggedIn()) {
                // Als we niet ingelogd zijn tonen we het login scherm voor volgende pagina's
                if (count($parts) == 1) {
                    // overview
                    return true;
                }

                if (count($parts) == 3 && $parts[1] == 'afrekening') {
                    return true;
                }

                if (count($parts) == 2 && $parts[1] == 'attesten' && User::isLoggedIn()) {
                    // allow nog logged in

                } else {
                    return false;
                }
            }

            // Beveiligde sectie
            if (count($parts) == 1) {
                return true;
            }
            if (count($parts) == 2) {
                if ($parts[1] == 'broer-zus-toevoegen') {
                    return true;
                }
                if ($parts[1] == 'verleng-inschrijving') {
                    return true;
                }
                if ($parts[1] == 'gezin-nakijken') {
                    return true;
                }
                if ($parts[1] == 'ouder-toevoegen') {
                    return true;
                }
                if ($parts[1] == 'attesten') {
                    return true;
                }
            }

            if (count($parts) == 3) {
                if ($parts[1] == 'ouder-aanpassen' || $parts[1] == 'ouder-verwijderen' || $parts[1] == 'wachtwoord-instellen') {
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

    public function getPage($url, $parts)
    {
        if ($url == 'inschrijven') {
            require __DIR__ . '/pages/overview.php';
            return new Pages\Overview();
        }

        if (count($parts) >= 1 && $parts[0] == 'ouders') {

            // Niet ingelogd
            if (!Ouder::isLoggedIn() && !(count($parts) == 2 && $parts[1] == 'attesten' && User::isLoggedIn())) {
                // Toon de pagina van de users module
                return new Login();
            }

            if (count($parts) == 2) {
                // Broer zus toevoegen of verlengen enkel in inschrijvingsperiode:
                if (!Inschrijving::isInschrijvingsPeriode()) {
                    require __DIR__ . '/pages/buiten-inschrijvingen-periode.php';
                    return new Pages\BuitenInschrijvingenPeriode();
                }

                if ($parts[1] == 'broer-zus-toevoegen') {
                    require __DIR__ . '/pages/broer-zus-toevoegen.php';
                    return new Pages\BroerZusToevoegen();
                }

                if ($parts[1] == 'verleng-inschrijving') {
                    require __DIR__ . '/pages/verleng-inschrijving.php';
                    return new Pages\VerlengInschrijving();
                }

                if ($parts[1] == 'gezin-nakijken') {
                    require __DIR__ . '/pages/gezin-nakijken.php';
                    return new Pages\GezinNakijken();
                }

                if ($parts[1] == 'ouder-toevoegen') {
                    require __DIR__ . '/pages/ouder-aanpassen.php';
                    return new Pages\OuderAanpassen();
                }

                if ($parts[1] == 'attesten') {
                    require __DIR__ . '/pages/ouder-attesten.php';
                    return new Pages\OuderAttesten();
                }
            }

            // Beveiligde sectie: reeds authenticatie gedaan
            if (count($parts) == 3) {
                if ($parts[1] == 'ouder-aanpassen' && !empty($this->ouder)) {
                    require __DIR__ . '/pages/ouder-aanpassen.php';
                    return new Pages\OuderAanpassen($this->ouder);
                }

                if ($parts[1] == 'ouder-verwijderen' && !empty($this->ouder)) {
                    require __DIR__ . '/pages/ouder-verwijderen.php';
                    return new Pages\OuderVerwijderen($this->ouder);
                }

                if ($parts[1] == 'wachtwoord-instellen' && !empty($this->ouder)) {
                    require __DIR__ . '/pages/ouder-wachtwoord-instellen.php';
                    return new Pages\OuderWachtwoordInstellen($this->ouder);
                }

                if ($parts[1] == 'lid-aanpassen' && !empty($this->lid)) {
                    require __DIR__ . '/pages/broer-zus-toevoegen.php';
                    return new Pages\BroerZusToevoegen($this->lid);
                }

                if ($parts[1] == 'steekkaart' && !empty($this->lid)) {
                    require __DIR__ . '/pages/steekkaart.php';
                    return new Pages\EditSteekkaart($this->lid);
                }
                if ($parts[1] == 'afrekening' && !empty($this->afrekening)) {
                    require __DIR__ . '/pages/afrekening.php';
                    return new Pages\ViewAfrekening($this->afrekening);
                }
            }

            if (count($parts) == 1) {
                require __DIR__ . '/pages/ouder-overview.php';
                return new Pages\OuderOverview();
            }
        }

        if (!Inschrijving::isInschrijvingsPeriode()) {
            require __DIR__ . '/pages/buiten-inschrijvingen-periode.php';
            return new Pages\BuitenInschrijvingenPeriode();
        }

        if (count($parts) == 2 && $parts[0] == 'inschrijven' && $parts[1] == 'uitzondering-toelaten') {
            require __DIR__ . '/pages/uitzondering-toelaten.php';
            return new Pages\UitzonderingToelaten();
        }

        require __DIR__ . '/pages/nieuw-gezin.php';
        return new Pages\NieuwGezin();
    }
}
