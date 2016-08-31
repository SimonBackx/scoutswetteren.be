<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;

class OuderOverview extends Page {
    public $redirect = null;

    function getStatusCode() {
        // Controle of alles in orde is, anders doorverwijzen
        $leden = Lid::getLedenForOuder(Ouder::getUser()->id);
        $scoutsjaar = Lid::getScoutsjaar();

        // Eerst: controleren of er leden ingeschreven zijn in huidig scoutsjaar, anders inschrijfpagina tonen
        $ingeschreven_aantal = 0;
        $nooit_ingeschreven_aantal = 0;

        foreach ($leden as $lid) {
            if (empty($lid->inschrijving)) {

                $nooit_ingeschreven_aantal ++;
                continue;
            }

            if ($lid->inschrijving->scoutsjaar == $scoutsjaar) {
                $ingeschreven_aantal++;
            }
        }

        if (Inschrijving::isInschrijvingsPeriode()) {
            if ($nooit_ingeschreven_aantal == count($leden)) {
                foreach ($leden as $lid) {
                    $lid->schrijfIn();
                }
            } elseif ($ingeschreven_aantal == 0) {
                // doorverwijzen!
                // 
                // TODO!! Voor inschrijvingen te verlengen!
                $this->redirect = 'ouders/verleng-inschrijving';
                return 302;
            }
        }
        
        // Tweede: controleren of alles steekkaarten van deze leden recent zijn nagekeken of bestaan
        foreach ($leden as $lid) {
            if (empty($lid->steekkaart) || $lid->steekkaart->moetNagekekenWorden()) {
                $this->redirect = "ouders/steekkaart/".$lid->id;
                return 302;
            }
        }

        // Derde: controleren of de betaalpagina al getoond werd, en die evt tonen
        // Afrekening aanmaken indien inschrijvingen nog niet zijn afgerekend.
        // Mailen én
        //  dan doorverwijzen naar de info pagina van deze afrekening
        foreach ($leden as $lid) {
            if (!empty($lid->inschrijving) && $lid->inschrijving->scoutsjaar == $scoutsjaar && empty($lid->inschrijving->afrekening)) {
                $this->redirect = "ouders/afrekening/".$lid->id;
                return 302;
            }
        }

        return 200;
    }

    function getContent() {
        if (!empty($this->redirect)) {
            header("Location: https://".$_SERVER['SERVER_NAME']."/".$this->redirect);
            return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/".$this->redirect;
        }
        

        return 'Correct ingelogd als ouder (placeholder)';
    }
}