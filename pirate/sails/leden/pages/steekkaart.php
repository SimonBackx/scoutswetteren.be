<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Users\User;
use Pirate\Model\Leden\Steekkaart;
use Pirate\Database\Database;

class EditSteekkaart extends Page {
    private $lid;

    function __construct($lid) {
        $this->lid = $lid;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Kijken of de steekkaart al geset is
        $new = empty($this->lid->steekkaart) || !$this->lid->steekkaart->isIngevuld();
        $success = false;
        $fail = false;
        $errors = array();

        if (empty($this->lid->steekkaart)) {
            $steekkaart = new Steekkaart();
            $steekkaart->setLid($this->lid);
        } else {
            $steekkaart = $this->lid->steekkaart;
        }

        if (!$steekkaart->moetNagekekenWorden()) {
            // Als overslaan toegestaan is
            
            if (isset($_POST['overslaan'])) {
                if ($steekkaart->save()) {
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                }
                $errors[] = 'Er ging iets mis bij het overslaan.';
            }
        }

        $bereikbaarheid_errors = array();
        $deelname_errors = array();
        $medische_errors = array();
        $aanvullende_errors = array();
        $bevestiging_errors = array();

        $data = array(
            'contactpersoon_naam' => '',
            'contactpersoon_gsm' => '',
            'contactpersoon_functie' => '',
            'verblijfsinstelling' => '',
            'deelname_onmogelijke_activiteiten_radio' => '',
            'deelname_onmogelijke_activiteiten' => '',
            'deelname_reden' => '',
            'deelname_sporten' => '',
            'deelname_hygiene' => '',
            'deelname_sociaal' => '',
            'deelname_andere' => '',
            'medisch_toestemming_medicatie' => '',
            'medisch_specifieke_medicatie' => '',
            'medisch_ziekten_checkbox' => '',
            'medisch_ziekten' => '',
            'medisch_ziekten_aanpak' => '',
            'medisch_dieet_checkbox' => '',
            'medisch_dieet' => '',
            'medisch_klem_checkbox' => '',
            'medisch_klem_jaar' => '',
            'huisarts_naam' => '',
            'huisarts_telefoon' => '',
            'bloedgroep' => '',
            'toestemming_fotos' => '',
            'aanvullend_voeding' => '',
            'aanvullend_andere' => '',
            'nagekeken_door' => User::getUser()->firstname.' '.User::getUser()->lastname,
            'nagekeken_door_titel' => ''
        );

        if (!empty($this->lid->steekkaart) && $this->lid->steekkaart->isIngevuld()) {
            $data = array(
                'contactpersoon_naam' => $steekkaart->contactpersoon_naam,
                'contactpersoon_gsm' => $steekkaart->contactpersoon_gsm,
                'contactpersoon_functie' => $steekkaart->contactpersoon_functie,
                'verblijfsinstelling' => $steekkaart->verblijfsinstelling,
                'deelname_onmogelijke_activiteiten_radio' => (empty($steekkaart->deelname_onmogelijke_activiteiten)?'ja':'nee'),
                'deelname_onmogelijke_activiteiten' => $steekkaart->deelname_onmogelijke_activiteiten,
                'deelname_reden' => $steekkaart->deelname_reden,
                'deelname_sporten' => $steekkaart->deelname_sporten,
                'deelname_hygiene' => $steekkaart->deelname_hygiene,
                'deelname_sociaal' => $steekkaart->deelname_sociaal,
                'deelname_andere' => $steekkaart->deelname_andere,
                'medisch_toestemming_medicatie' => $steekkaart->medisch_toestemming_medicatie,
                'medisch_specifieke_medicatie' => $steekkaart->medisch_specifieke_medicatie,
                'medisch_ziekten_checkbox' => (empty($steekkaart->medisch_ziekten)?'nee':'ja'),
                'medisch_ziekten' => $steekkaart->medisch_ziekten,
                'medisch_ziekten_aanpak' => $steekkaart->medisch_ziekten_aanpak,
                'medisch_dieet_checkbox' => (empty($steekkaart->medisch_dieet)?'nee':'ja'),
                'medisch_dieet' => $steekkaart->medisch_dieet,
                'medisch_klem_checkbox' => (empty($steekkaart->medisch_klem_jaar)?'nee':'ja'),
                'medisch_klem_jaar' => $steekkaart->medisch_klem_jaar,
                'huisarts_naam' => $steekkaart->huisarts_naam,
                'huisarts_telefoon' => $steekkaart->huisarts_telefoon,
                'bloedgroep' => $steekkaart->bloedgroep,
                'toestemming_fotos' => $steekkaart->toestemming_fotos,
                'aanvullend_voeding' => $steekkaart->aanvullend_voeding,
                'aanvullend_andere' => $steekkaart->aanvullend_andere,
                'nagekeken_door' => User::getUser()->firstname.' '.User::getUser()->lastname,
                'nagekeken_door_titel' => ''
            );
        }

        $ingevuld = false;
        foreach ($data as $key => $value) {
            if (isset($_POST[$key])) {
                $data[$key] = $_POST[$key];
                $ingevuld = true;
            }
        }

        if ($ingevuld) {
            $fail = !$steekkaart->setProperties($data, $bereikbaarheid_errors, $deelname_errors, $medische_errors, $aanvullende_errors, $bevestiging_errors);

            if (!$fail) {
                // Opslaan
                // 
                
                $success = $steekkaart->save();

                if (!$success) {
                    $errors[] = 'Er ging iets mis bij het opslaan: "'.Database::getDb()->error.'" Neem contact op met de webmaster';
                } else {
                    // Doorverwijzen
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                }
            }
        }

        
        return Template::render('leden/steekkaart', array(
            'lid' => $this->lid,
            'new' => $new,
            'data' => $data,
            'success' => $success,
            'fail' => $fail,
            'errors' => $errors,
            'moetNagekekenWorden' => $steekkaart->moetNagekekenWorden(),
            'nagekekenString' => $steekkaart->getNagekekenString(),
            'bereikbaarheid_errors' => $bereikbaarheid_errors,
            'deelname_errors' => $deelname_errors,
            'medische_errors' => $medische_errors,
            'aanvullende_errors' => $aanvullende_errors,
            'bevestiging_errors' => $bevestiging_errors
        ));
    }
}