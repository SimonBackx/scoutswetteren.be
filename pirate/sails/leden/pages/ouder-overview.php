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

class OuderOverview extends Page {
    public $redirect = null;
    public $leden = array();
    protected $ouders = array();

    function getStatusCode() {
        // Controle of alles in orde is, anders doorverwijzen
        $user = Ouder::getUser();
        $leden = Lid::getLedenForOuder($user);
        $this->leden = $leden;
        $scoutsjaar = Inschrijving::getScoutsjaar();

        // Eerst: controleren of er leden ingeschreven zijn in huidig scoutsjaar, anders inschrijfpagina tonen
        $ingeschreven_aantal = 0;
        $nooit_ingeschreven_aantal = 0;

        foreach ($leden as $lid) {
            if (empty($lid->inschrijving)) {

                $nooit_ingeschreven_aantal ++;
                continue;
            }

            if ($lid->isIngeschreven()) {
                $ingeschreven_aantal++;
            }
        }

        if (Inschrijving::isInschrijvingsPeriode()) {
            if ($nooit_ingeschreven_aantal == count($leden)) {
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
            if ($lid->isInschrijfbaar() && ($lid->isIngeschreven() || empty($lid->inschrijving)) // Ingeschreven of zal automatisch worden ingeschreven
                  && 
                 (empty($lid->steekkaart) || $lid->steekkaart->moetNagekekenWorden()) // Steekkaart niet in orde
            ) {
                $this->redirect = "ouders/steekkaart/".$lid->id;
                return 302;
            }
        }

        // Derde: controleren of de betaalpagina al getoond werd, en die evt tonen
        // Afrekening aanmaken indien inschrijvingen nog niet zijn afgerekend.
        // Mailen én
        //  dan doorverwijzen naar de info pagina van deze afrekening
        
        // Alle nog nooit ingeschreven leden (nieuwe leden), nu pas inschrijven (nadat hun steekkaart dus is ingevuld)
        
        /*foreach ($leden as $lid) {
            if ($lid->isInschrijfbaar() && empty($lid->inschrijving)) {
                $lid->schrijfIn();
            }
        }*/

        // Nakijken
        foreach ($leden as $lid) {
            if ($lid->isInschrijfbaar() && $lid->moetNagekekenWorden()) {
                $this->redirect = "ouders/lid-aanpassen/".$lid->id;
                return 302;
            }
        }

        // Gezin checken
        if (Ouder::getUser()->gezin->scoutsjaar_checked != $scoutsjaar) {
            $this->redirect = "ouders/gezin-nakijken";
            return 302;
        }


        $inschrijvingen_afrekenen = array();
        $leden_waarvoor_afgerekend = array();
        foreach ($leden as $lid) {
            if ($lid->isIngeschreven() && empty($lid->inschrijving->afrekening)) {
                $inschrijvingen_afrekenen[] = $lid->inschrijving;
                $leden_waarvoor_afgerekend[] = $lid;
            }
        }

        if (count($inschrijvingen_afrekenen) > 0) {
            $afrekening = Afrekening::createForInschrijvingen($inschrijvingen_afrekenen);
            if (!is_null($afrekening)) {

                $mail = new Mail('Afrekening lidgeld', 'afrekening', array('leden' => $leden_waarvoor_afgerekend));

                $ouder = Ouder::getUser();
                $mail->addTo(
                    $ouder->user->mail, 
                    array('naam' => $ouder->user->firstname, 'url' => "https://".$_SERVER['SERVER_NAME']."/ouders/afrekening/".$afrekening->id.'/'),
                    $ouder->user->firstname.' '.$ouder->user->lastname
                );

                $mail->send();

                $this->redirect = "ouders/afrekening/".$afrekening->id.'/?klaar';
                return 302;
            } else {
                echo 'Het afrekenen is mislukt. Neem contact op met website@scoutswetteren.be om de afrekening in orde te maken (voor het betalen van het lidgeld).';
            }
        }

        return 200;
    }

    function getContent() {
        if (!empty($this->redirect)) {
            header("Location: https://".$_SERVER['SERVER_NAME']."/".$this->redirect);
            return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/".$this->redirect;
        }

        $leden_ingeschreven = array();
        $niet_ingeschreven_aantal = 0;
        foreach ($this->leden as $lid) {
            if ($lid->isIngeschreven()) {
                $leden_ingeschreven[] = $lid;
            } else {
                $niet_ingeschreven_aantal++;
            }
        }
        $user = Ouder::getUser();
        $this->ouders = Ouder::getOudersForGezin($user->gezin->id);
        $afrekeningen = Afrekening::getAfrekeningenForGezin($user->gezin);
        $scoutsjaar = Inschrijving::getScoutsjaar();
        
        return Template::render('leden/ouder-overview', array(
            'leden' => $leden_ingeschreven,
            'niet_ingeschreven_aantal' => $niet_ingeschreven_aantal,
            'ouder' => $user,
            'afrekeningen' => $afrekeningen,
            'gezin' => $user->gezin,
            'ouders' => $this->ouders,
            'scoutsjaar' => $scoutsjaar
        ));
    }
}