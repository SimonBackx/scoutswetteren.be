<?php
namespace Pirate\Sail\Verhuur\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;
class VerhuurReserveren extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $error = false;
        $errors = array();
        $data = array('contact_land' => 'België');

        if (!isset($_POST['startdatum'], $_POST['einddatum'], $_POST['personen'], $_POST['personen_tenten'])) {
            $errors[] = 'Hmmm.... Er ging iets mis. Je hebt vast deze pagina herladen. Ga terug naar de verhuurpagina en maak je datum selectie en kom nog eens terug.';
            // Cruciale fout
        } else {
            $data = array(
                'startdatum' => $_POST['startdatum'],
                'einddatum' => $_POST['einddatum'],
                'personen' => intval($_POST['personen']),
                'personen_tenten' => intval($_POST['personen_tenten'])
            );

            $reservatie = new Reservatie();

            if (isset($_POST['groep'], $_POST['contact_naam'], $_POST['contact_gsm'], $_POST['contact_email'], $_POST['contact_adres'], $_POST['contact_gemeente'], $_POST['contact_land'], $_POST['contact_postcode'], $_POST['info'], $_POST['opmerkingen'])) {

                $data['groep'] = $_POST['groep'];
                $data['contact_naam'] = $_POST['contact_naam'];
                $data['contact_gsm'] = $_POST['contact_gsm'];
                $data['contact_email'] = $_POST['contact_email'];
                $data['info'] = $_POST['info'];
                $data['opmerkingen'] = $_POST['opmerkingen'];
                $data['contact_adres'] = $_POST['contact_adres'];
                $data['contact_gemeente'] = $_POST['contact_gemeente'];
                $data['contact_postcode'] = $_POST['contact_postcode'];
                $data['contact_land'] = $_POST['contact_land'];
                
                $errors = $reservatie->setProperties($data); // basic controle zonder naam, gsm etc...
                if (count($errors) == 0) {
                    // Opslaan
                    if ($reservatie->save()) {
                        return Template::render('verhuur/verhuur-ontvangen', array());
                    } else {
                        $errors[] = 'Er ging iets mis bij het opslaan';
                    }
                }
            } else {
                $errors = $reservatie->setProperties($data, true); // basic controle zonder naam, gsm etc...
            }
        }
        return Template::render('verhuur/verhuur-reserveren', array(
            'error' => $error,
            'errors' => $errors,
            'data' => $data
        ));
    }
}