<?php
namespace Pirate\Sail\Verhuur\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class VerhuurReserveren extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $error = false;
        $data = array();

        if (!isset($_POST['aankomst'], $_POST['vertrek'], $_POST['personen'])) {
            $error = true;
            // Cruciale fout
        } else {
            $data = array(
                'aankomst' => $_POST['aankomst'],
                'vertrek' => $_POST['vertrek'],
                'personen' => intval($_POST['personen']),
                'personen_tenten' => intval($_POST['personen-tenten'])
            );


            // Basic controle uitvoeren
            
            // Als ongeldig: fout bericht tonen
            if ($data['personen'] < 1 || $data['personen']>60) {
                $error = true;
            }
            $startdate = \DateTime::createFromFormat('d-m-Y H:i', $data['aankomst'].' 0:00');
            if ($startdate === false) {
                $error = true;
            }

            $enddate = \DateTime::createFromFormat('d-m-Y H:i', $data['vertrek'].' 0:00');
            if ($enddate === false) {
                $error = true;
            }

            if (!$error) {
                $difference = $startdate->diff($enddate);
                $days = $difference->d;

                if ($days <= 2) {
                    $data['personen_tenten'] = 0;
                }

            }


            if (isset($_POST['naam'], $_POST['gsm'], $_POST['email'], $_POST['wie'], $_POST['opmerkingen'])) {
                $data['naam'] = $_POST['naam'];
                $data['gsm'] = $_POST['gsm'];
                $data['email'] = $_POST['email'];
                $data['wie'] = $_POST['wie'];
                $data['opmerkingen'] = $_POST['opmerkingen'];
            } else {
                
            }
        }
        return Template::render('verhuur/verhuur-reserveren', array(
            'error' => $error,
            'data' => $data
        ));
    }
}