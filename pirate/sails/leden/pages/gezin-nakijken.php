<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Gezin;
use Pirate\Database\Database;
use Pirate\Mail\Mail;

// Deze pagina mag enkel getoond worden als de ouder (tijdelijk) ingelogd is
class GezinNakijken extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
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
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                } else {
                    $errors[] = 'Fout bij opslaan. Contacteer de webmaster.';
                }
            }
        }
        
        return Template::render('leden/gezin-nakijken', array(
            'success' => $success,
            'errors' => $errors,
            'gezin' => $data
        ));
    }
}