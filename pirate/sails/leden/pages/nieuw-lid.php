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

class NieuwLid extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        $fail = false;
        $success = false;

        $leden = array();
        $leden_models = array();
        $errors = array();

        $ouders = array();
        $ouders_models = array();
        $gezin_data = array();
        // check of vanalles is geset
        if (isset(
            $_POST['lid-voornaam'], 
            $_POST['lid-achternaam'], 
            $_POST['lid-geboortedatum-dag'],
            $_POST['lid-geboortedatum-maand'],
            $_POST['lid-geboortedatum-jaar'],
            $_POST['lid-gsm'],
            $_POST['ouder-titel'],
            $_POST['ouder-voornaam'],
            $_POST['ouder-achternaam'],
            $_POST['ouder-adres'],
            $_POST['ouder-gemeente'],
            $_POST['ouder-postcode'],
            $_POST['ouder-gsm'],
            $_POST['ouder-telefoon'],
            $_POST['ouder-email'],
            $_POST['gezinssituatie']
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
                if (count($data['errors']) > 0) {
                    $fail = true;
                }

                $leden_models[] = $lid;

                // Opslaan
                $leden[] = $data;
            }

            // Alle ouders overlopen
            $aantal_ouders = count($_POST['ouder-voornaam']) - 1;
            for ($i=0; $i < $aantal_ouders; $i++) { 
                $data = array(
                    'titel' => $_POST['ouder-titel'][$i],
                    'voornaam' => $_POST['ouder-voornaam'][$i],
                    'achternaam' => $_POST['ouder-achternaam'][$i],
                    'adres' => $_POST['ouder-adres'][$i],
                    'gemeente' => $_POST['ouder-gemeente'][$i],
                    'postcode' => $_POST['ouder-postcode'][$i],
                    'telefoon' => $_POST['ouder-telefoon'][$i],
                    'gsm' => $_POST['ouder-gsm'][$i],
                    'email' => $_POST['ouder-email'][$i]
                );

                // Controleren en errors setten
                $ouder = new Ouder();
                $data['errors'] = $ouder->setProperties($data);
                if (count($data['errors']) > 0) {
                    $fail = true;
                }

                $ouder_models[] = $ouder;

                // Opslaan
                $ouders[] = $data;
            }

            // Gezin
            $gezin = new Gezin();
            $data = array(
                'gezinssituatie' => $_POST['gezinssituatie'],
                'scouting_op_maat' => false
            );
            if (isset($_POST['scouting_op_maat'])) {
                $data['scouting_op_maat'] = true;  
            }
            if (count($gezin->setProperties($data)) > 0) {
                $fail = true;
            }
            $gezin_data = $data;

            if ($fail == false) {
                // Gezin opslaan
                $success = $gezin->save();

                if (!$success) {
                             $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                } else {

                    // Leden aan gezin toevoegen
                    foreach ($leden_models as $lid) {
                        $lid->setGezin($gezin);
                        $success = $lid->save();
                        if ($success == false) {
                             $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                            break;
                        }
                    }

                    if ($success) {
                        // Ouders aan gezin toevoegen
                        foreach ($ouder_models as $ouder) {
                            $ouder->setGezin($gezin);
                            $success = $ouder->save();
                            if ($success == false) {
                             $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                                break;
                            }
                        }

                        if ($success) {
                        
                            // Password generator mails maken en versturen
                            
                            // yay!
                            $mail = new Mail('Inschrijving bij de scouts - Account aanmaken', 'nieuw-lid', array('leden' => $leden, 'ouders' => $ouders));
                            foreach ($ouder_models as $ouder) {
                                $mail->addTo(
                                    $ouder->email, 
                                    array('naam' => $ouder->voornaam, 'url' => $ouder->getSetPasswordUrl()),
                                    $ouder->voornaam.' '.$ouder->achternaam
                                );
                            }
                            if (!$mail->send()) {
                                $errors[] = 'Er ging iets mis met het versturen van de e-mails. Contacteer de webmaster.';
                                $success = false;
                            } else {
                                return Template::render('leden/nieuw-lid-gelukt', array(
                                    'leden' => $leden,
                                    'ouders' => $ouders,
                                    'gezin' => $gezin_data,
                                ));
                            }
                        }
                    }
                }
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
            'ouders' => $ouders,
            'gezin' => $gezin_data,
            'titels' => Ouder::$titels,
            'maanden' => $config["months"],
            'jaren' => $jaren,
            'fail' => $fail,
            'success' => $success,
            'errors' => $errors,
            'takken' => json_encode($verdeling)
        ));
    }
}