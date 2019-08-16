<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class VerlengInschrijving extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Controle of alles in orde is, anders doorverwijzen
        $leden_allemaal = Lid::getLedenForOuder(Ouder::getUser());
        $leden = array();
        $this->leden = $leden;
        $scoutsjaar = Inschrijving::getScoutsjaar();
        $success = false;
        $errors = array();

        $al_ingeschreven = array();
        $niet_inschrijfbaar = array();

        foreach ($leden_allemaal as $lid) {
            if (!$lid->isIngeschreven()) {
                if (!$lid->isInschrijfbaar()) {
                    $niet_inschrijfbaar[] = $lid;
                    continue;
                }
                $leden[] = $lid;
            } else {
                $al_ingeschreven[] = $lid;
            }
        }

        if (isset($_POST['annuleren'])) {
            header("Location: https://" . $_SERVER['SERVER_NAME'] . '/ouders');
            return '';
        } elseif (isset($_POST['leden']) && is_array($_POST['leden'])) {
            if (count($_POST['leden']) == 0) {
                $errors[] = 'U moet zeker één iemand selecteren';
            } else {
                /// Schrijf alle leden in die geselecteerd werden
                foreach ($leden as $lid) {
                    foreach ($_POST['leden'] as $id) {
                        # code...
                        if ($id == $lid->id) {
                            $lid->schrijfIn();
                        }
                    }
                }
                $success = true;
                header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                return;
            }
        } elseif (isset($_POST['submit'])) {
            $errors[] = 'U moet zeker één iemand selecteren';
        }

        return Template::render('pages/leden/verleng-inschrijving', array(
            'leden' => $leden,
            'al_ingeschreven' => $al_ingeschreven,
            'niet_inschrijfbaar' => $niet_inschrijfbaar,
            'scoutsjaar' => $scoutsjaar,
            'success' => $success,
            'errors' => $errors,
            'limits_ignored' => Lid::areLimitsIgnored(),
        ));
    }
}
