<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leden\Afrekening;
use Pirate\Database\Database;

class ScoutingOpMaat extends Page {
    private $gezin;
    private $inschakelen;

    function __construct(Gezin $gezin, $inschakelen = false) {
        $this->gezin = $gezin;
        $this->inschakelen = $inschakelen;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;

        $scoutsjaar = Inschrijving::getScoutsjaar();
        $afrekeningen_allemaal = Afrekening::getAfrekeningenForGezin($this->gezin);
        $afrekeningen = array();

        // Alle afrekeningen van het huidige scoutsjaar verzamelen, deze zullen worden
        // aangepast
        foreach ($afrekeningen_allemaal as $afrekening) {
            if ($afrekening->inschrijvingen[0]->scoutsjaar == $scoutsjaar) {
                $afrekeningen[] = $afrekening;
            }
        }

        if (isset($_POST['confirm'])) {
            // Echt verwijderen en doorverwijzen
            $this->gezin->scouting_op_maat = $this->inschakelen;
            if ($this->gezin->save()) {
                $success = true;
                foreach ($afrekeningen as $afrekening) {
                    if ($this->inschakelen) {
                        $afrekening->betaald_scouts = $afrekening->getNogTeBetalenFloat();
                    } else {
                        $afrekening->betaald_scouts = 0;
                    }
                    $afrekening->save();
                }

                // Alle afrekeningen ook corrigeren
                header("Location: https://".$_SERVER['SERVER_NAME']."/admin/inschrijvingen");
            } else {
                echo 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';

            }

            
        }

        return Template::render('leden/admin/scouting-op-maat', array(
            'gezin' => $this->gezin,
            'afrekeningen' => $afrekeningen,
            'inschakelen' => $this->inschakelen,
            'success' => $success
        ));
    }
}