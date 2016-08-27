<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;

class NieuwLid extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        $leden = array();
        // check of vanalles is geset
        if (isset(
            $_POST['lid-voornaam'], 
            $_POST['lid-achternaam'], 
            $_POST['lid-geboortedatum-dag'],
            $_POST['lid-geboortedatum-maand'],
            $_POST['lid-geboortedatum-jaar'],
            $_POST['lid-gsm'],
            $_POST['ouder-functie'],
            $_POST['ouder-voornaam'],
            $_POST['ouder-achternaam'],
            $_POST['ouder-adres'],
            $_POST['ouder-gemeente'],
            $_POST['ouder-telefoon'],
            $_POST['ouder-email'],
            $_POST['afspraken-gezinssituatie']
        )) {
            // Hoeveel leden opgegeven?
            $aantal_leden = count($_POST['lid-voornaam']) - 1;
            for ($i=0; $i < $aantal_leden; $i++) { 
                $data = array(
                    'voornaam' => $_POST['lid-voornaam'][$i],
                    'achternaam' => $_POST['lid-achternaam'][$i],
                    'geboortedatum_dag' => $_POST['lid-geboortedatum-dag'][$i],
                    'geboortedatum_maand' => $_POST['lid-geboortedatum-maand'][$i],
                    'geboortedatum_jaar' => $_POST['lid-geboortedatum-jaar'][$i],
                    'gsm' => $_POST['lid-gsm'][$i],
                    'geslacht' => ''
                );

                if (isset($_POST['lid-geslacht'][$i])) {
                    $data['geslacht'] = $_POST['lid-geslacht'][$i];
                }

                // Controleren en errors setten
                $lid = new Lid();
                $data['errors'] = $lid->setProperties($data);

                // Opslaan
                $leden[] = $data;
            }
        }
        $jaar = Lid::getScoutsjaar();
        $verdeling = Lid::getTakkenVerdeling($jaar);
        $keys = array_keys($verdeling);
        sort($keys);
        $jaren = array();
        for ($i=$keys[0] - 5; $i < $jaar; $i++) { 
            $jaren[] = $i;
        }

        return Template::render('leden/nieuw-lid', array(
            'leden' => $leden,
            'titels' => array('Mama', 'Papa', 'Voogd', 'Stiefmoeder', 'Stiefvader'),
            'maanden' => $config["months"],
            'jaren' => $jaren,
            'takken' => json_encode($verdeling)
        ));
    }
}