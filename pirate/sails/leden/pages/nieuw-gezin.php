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
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Users\User;

class NieuwGezin extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        // todo: require logged in!

        $fail = false;
        $success = false;

        /*$leden = array();
        $leden_models = array();*/
        $errors = array();

        $user = User::getUser();
        $ouders = [
            [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
                'mail' => $user->mail
            ]
        ];
        $ouders_models = array();



        $gezin_data = array();



        // check of vanalles is geset
        if (isset(
            /*$_POST['lid-voornaam'], 
            $_POST['lid-achternaam'], 
            $_POST['lid-geboortedatum-dag'],
            $_POST['lid-geboortedatum-maand'],
            $_POST['lid-geboortedatum-jaar'],
            $_POST['lid-gsm'],*/
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
            /*$aantal_leden = count($_POST['lid-voornaam']) - 1;
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

            if (count($leden_models) < 1) {
                $errors[] = 'Er ging iets mis. Controleer of javascript ingeschakeld is en of het formulier dat u heeft doorgestuurd niet door malware werd aangepast. Controleer ook of u een moderne browser gebruikt.';
                $fail = true;
            }*/

            // Alle ouders overlopen
            $aantal_ouders = min(isset($_POST['eenoudergezin']) ? 1 : 2, count($_POST['ouder-voornaam']));
            $emailadressen = [];

            for ($i=0; $i < $aantal_ouders; $i++) { 
                $data = array(
                    'titel' => $_POST['ouder-titel'][$i],
                    'firstname' => $_POST['ouder-voornaam'][$i],
                    'lastname' => $_POST['ouder-achternaam'][$i],
                    'adres' => $_POST['ouder-adres'][$i],
                    'gemeente' => $_POST['ouder-gemeente'][$i],
                    'postcode' => $_POST['ouder-postcode'][$i],
                    'telefoon' => $_POST['ouder-telefoon'][$i],
                    'phone' => $_POST['ouder-gsm'][$i],
                    'mail' => $_POST['ouder-email'][$i]
                );

                // Controleren en errors setten
                $ouder = new Ouder();
                if ($i == 0) {
                    $ouder->user = User::getUser();
                } 

                $data['errors'] = $ouder->setProperties($data);
                if (isset($emailadressen[$ouder->user->mail])) {
                    $data['errors'][] = 'Het is niet toegestaan dat je hetzelfde e-mailadres gebruikt voor meerdere ouders. Elke ouder krijgt namelijk een apart account waarmee hij/zij kan inloggen.';
                }

                if (count($data['errors']) > 0) {
                    $fail = true;
                } else {
                    $emailadressen[$ouder->user->mail] = true;
                }

                $ouder_models[] = $ouder;

                // Opslaan
                $ouders[$i] = $data;
                
            }

            if (count($ouder_models) < 1) {
                $errors[] = 'Er ging iets mis. Controleer of javascript ingeschakeld is en of het formulier dat u heeft doorgestuurd niet door malware werd aangepast. Controleer ook of u een moderne browser gebruikt.';
                $fail = true;
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

            $data['eenoudergezin'] = isset($_POST['eenoudergezin']);
            $gezin_data = $data;

            if ($fail == false) {
                // Gezin opslaan
                $success = $gezin->save();

                if (!$success) {
                    $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                } else {

                    // Leden aan gezin toevoegen
                    /*foreach ($leden_models as $lid) {
                        $lid->setGezin($gezin);
                        $success = $lid->save();
                        if ($success == false) {
                             $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                            break;
                        }
                    }*/

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

                            $first = true;
                            foreach ($ouder_models as $ouder) {
                                if ($first) {
                                    $first = false;
                                    continue;
                                }
                                $ouder->sendCreatedMail($ouder_models[0]->user);
                            }
                        
                            header("Location: https://".$_SERVER['SERVER_NAME']."/ouders/broer-zus-toevoegen");
                            return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders/broer-zus-toevoegen";
                        }
                    }
                }
            }
        }
        
        
        /*$jaar = Inschrijving::getScoutsjaar();
        $verdeling = Lid::getTakkenVerdeling($jaar, Lid::areLimitsIgnored());
        $keys = array_keys($verdeling);
        sort($keys);
        $jaren = array();
        for ($i=$keys[0] - 5; $i < $jaar; $i++) { 
            $jaren[] = $i;
        }*/

        return Template::render('leden/nieuw-lid', array(
            //'leden' => $leden,
            'ouders' => $ouders,
            'gezin' => $gezin_data,
            'titels' => Ouder::$titels,
            //'maanden' => $config["months"],
            //'jaren' => $jaren,
            'fail' => $fail,
            'success' => $success,
            'errors' => $errors,
            //'takken' => json_encode($verdeling),
            //'limits_ignored' => Lid::areLimitsIgnored(),
        ));
    }
}