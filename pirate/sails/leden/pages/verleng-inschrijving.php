<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leden\Afrekening;
use Pirate\Mail\Mail;

class VerlengInschrijving extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Controle of alles in orde is, anders doorverwijzen
        $leden_allemaal = Lid::getLedenForOuder(Ouder::getUser()->id);
        $leden = array();
        $this->leden = $leden;
        $scoutsjaar = Inschrijving::getScoutsjaar();
        $success = false;
        $errors = array();

        $al_ingeschreven = array();

        foreach ($leden_allemaal as $lid) {
            if (!$lid->isIngeschreven()) {
                $leden[] = $lid;
            } else {
                $al_ingeschreven[] = $lid;
            }
        }


        if (isset($_POST['annuleren'])) {
            if (count($al_ingeschreven) == 0) {
                $errors[] = 'Doorverwijzen naar hoofdpagina...'; // Dit zal nooit optreden, toch extra veiligheid
                header("Location: https://".$_SERVER['SERVER_NAME']);
            } else {
                header("Location: https://".$_SERVER['SERVER_NAME'].'/ouders');
            }   
        }
        elseif (isset($_POST['leden']) && is_array($_POST['leden'])) {
            if (count($_POST['leden']) == 0) {
                $errors[] = 'U moet zeker één iemand selecteren'; // Dit zal nooit optreden, toch extra veiligheid
            } else {
                foreach ($leden as $lid) {
                    foreach ($_POST['leden'] as $id) {
                        # code...
                        if ($id == $lid->id) {
                            $lid->schrijfIn();
                        }
                    }
                    $success = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                }
            }
        }
        elseif (isset($_POST['submit'])) {
            $errors[] = 'U moet zeker één iemand selecteren'; // Dit zal nooit optreden, toch extra veiligheid
        }
        
        return Template::render('leden/verleng-inschrijving', array(
            'leden' => $leden,
            'al_ingeschreven' => $al_ingeschreven,
            'scoutsjaar' => $scoutsjaar,
            'success' => $success,
            'errors' => $errors
        ));
    }
}