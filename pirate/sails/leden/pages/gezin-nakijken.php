<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class GezinNakijken extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        if (!Ouder::isLoggedIn()) {
            return 'Error!';
        }
        $ouder = Ouder::getUser();
        $gezin = $ouder->gezin;

        $errors = array();
        $success = false;

        $data = array(
            'gezinssituatie' => $gezin->gezinssituatie,
            'scouting_op_maat' => $gezin->scouting_op_maat,
        );

        if (isset($_POST['gezinssituatie'])) {
            // Formulier is verzonden
            $data['gezinssituatie'] = $_POST['gezinssituatie'];

            if (isset($_POST['scouting_op_maat'])) {
                $data['scouting_op_maat'] = true;
            } else {
                $data['scouting_op_maat'] = false;
            }

            $errors = $gezin->setProperties($data);
            if (count($errors) == 0) {
                if ($gezin->save()) {
                    $success = true;
                    header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                    return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders";
                } else {
                    $errors[] = 'Fout bij opslaan. Contacteer de webmaster.';
                }
            }
        }

        return Template::render('pages/leden/gezin-nakijken', array(
            'success' => $success,
            'errors' => $errors,
            'gezin' => $data,
            'scouting_op_maat_tekst' => Environment::getSetting('scouting_op_maat.checkbox', 'Bedankt, onze takleiding bespreekt dit graag persoonlijk en discreet op een huisbezoek.'),
        ));
    }
}
