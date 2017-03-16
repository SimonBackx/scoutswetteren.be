<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class SteekkaartOverzicht extends Page {
    public $tak = '';
    function __construct($tak = '') {
        $this->tak = $tak;
    }

    function getStatusCode() {
        return 200;
    }

    function hasOwnLayout() {
        return true;
    }

    function getContent() {
        $user = Leiding::getUser();

        $tak = $this->tak;

        if (empty($tak) && !empty($user->tak)) {
            $tak = $user->tak;
        }

        $leden = Lid::getLedenForTakFull($tak);

        $toon_steekkaart = true;

        if (isset($_GET['kort'])) {
            $toon_steekkaart = false;
        }

        if ($toon_steekkaart) {
            // Foto's toelating
            $fotoToelating = array('title' => 'Toelating foto\'s', 'table' => array());
            $onmogelijke_activiteiten = array('title' => 'Onmogelijke activiteiten', 'table' => array());
            $aandacht_sporten = array('title' => 'Aandacht bij sporten', 'table' => array());
            $aandacht_sociale_omgang = array('title' => 'Aandacht bij sociale omgang', 'table' => array());
            $aandacht_hygiene = array('title' => 'Aandacht bij hygiëne', 'table' => array());
            $aandacht_andere = array('title' => 'Andere aandachtspunten', 'table' => array());

            $toestemming_medicatie = array('title' => 'Toestemming toedienen medicatie', 'table' => array());
            $medicatie = array('title' => 'Specifieke medicatie krijgen (doktersattest voor kamp/weekend vragen)', 'table' => array());
            $ziekten = array('title' => 'Ziekten', 'table' => array());
            $klem = array('title' => 'Geen vaccinatie tetanus (klem)', 'table' => array());

            $dieet = array('title' => 'Speciaal dieet volgen', 'table' => array());

            $aanvullende_opmerkingen_voeding = array('title' => 'Aanvullende opmerkingen voeding', 'table' => array());
            $aanvullende_opmerkingen = array('title' => 'Aanvullende opmerkingen', 'table' => array());
            $geen_steekkaart = array('title' => 'Steekkaart niet ingevuld / nagekeken', 'table' => array());

            $year = date("Y") - 10;

            foreach ($leden as $lid) {
                if (isset($lid->steekkaart) && $lid->steekkaart->isIngevuld()) {
                    if ($lid->steekkaart->toestemming_fotos == 'nee') {
                        $fotoToelating['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Nee';
                    }

                    if (isset($lid->steekkaart->deelname_onmogelijke_activiteiten)) {
                        $onmogelijke_activiteiten['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->deelname_onmogelijke_activiteiten . ', '. $lid->steekkaart->deelname_reden;
                    }

                    if (isset($lid->steekkaart->deelname_sporten) && strlen($lid->steekkaart->deelname_sporten) > 0) {
                        $aandacht_sporten['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->deelname_sporten;
                    }

                    if (isset($lid->steekkaart->deelname_sociaal) && strlen($lid->steekkaart->deelname_sociaal) > 0) {
                        $aandacht_sociale_omgang['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->deelname_sociaal;
                    }

                    if (isset($lid->steekkaart->deelname_hygiene) && strlen($lid->steekkaart->deelname_hygiene) > 0) {
                        $aandacht_hygiene['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->deelname_hygiene;
                    }

                    if (isset($lid->steekkaart->deelname_andere) && strlen($lid->steekkaart->deelname_andere) > 0) {
                        $aandacht_andere['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->deelname_andere;
                    }

                    if ($lid->steekkaart->medisch_toestemming_medicatie == 'nee') {
                        $toestemming_medicatie['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Nee';
                    }

                    if ($lid->steekkaart->medisch_specifieke_medicatie == 'ja') {
                        $medicatie['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Ja';
                    }

                    if (isset($lid->steekkaart->medisch_ziekten) && strlen($lid->steekkaart->medisch_ziekten) > 0) {
                        $ziekten['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->medisch_ziekten.'; Aanpak: '.$lid->steekkaart->medisch_ziekten_aanpak;
                    }

                    if (isset($lid->steekkaart->medisch_dieet) && strlen($lid->steekkaart->medisch_dieet) > 0) {
                        $dieet['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->medisch_dieet;
                    }

                    if (isset($lid->steekkaart->aanvullend_voeding) && strlen($lid->steekkaart->aanvullend_voeding) > 0) {
                        $aanvullende_opmerkingen_voeding['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->aanvullend_voeding;
                    }

                    if (isset($lid->steekkaart->aanvullend_andere) && strlen($lid->steekkaart->aanvullend_andere) > 0) {
                        $aanvullende_opmerkingen['table'][$lid->voornaam . ' ' . $lid->achternaam] = $lid->steekkaart->aanvullend_andere;
                    }

                    if (!isset($lid->steekkaart->medisch_klem_jaar)) {
                        $klem['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Niet ingeënt';
                    } elseif ($lid->steekkaart->medisch_klem_jaar <= $year){
                        $klem['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Te lang geleden ingeënt ('.$lid->steekkaart->medisch_klem_jaar.')';
                    }

                    if ($lid->steekkaart->moetNagekekenWorden()) {
                        $geen_steekkaart['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Moet nagekeken worden ('.$lid->steekkaart->getNagekekenString().')';
                    }
                } else {
                    $geen_steekkaart['table'][$lid->voornaam . ' ' . $lid->achternaam] = 'Niet ingevuld';
                }
            }


            $steekkaartSamenvatting = array($geen_steekkaart, $fotoToelating, $onmogelijke_activiteiten, $aandacht_sporten, $aandacht_sociale_omgang, $aandacht_hygiene, $aandacht_andere, $toestemming_medicatie, $medicatie, $ziekten, $klem, $dieet, $aanvullende_opmerkingen_voeding, $aanvullende_opmerkingen);

            return Template::render('leden/steekkaart/overzicht', array(
                'leden' => $leden,
                'samenvatting' => $steekkaartSamenvatting,
                'tak' => $tak
            ));
            
        }
        
        return Template::render('leden/steekkaart/overzicht', array(
            'leden' => $leden,
            'tak' => $tak
        ));
    }
}