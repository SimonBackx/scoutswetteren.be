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

class OuderAanpassen extends Page {
    private $ouder = null;

    function __construct($ouder = null) {
        $this->ouder = $ouder;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        $new = true;
        $success = false;
        $data = array();
        $errors = array();
        $id = null;

        if (isset($this->ouder)) {
            $new = false;
            $data = $this->ouder->getProperties();
            $id = $this->ouder->id;
        } else {
            $this->ouder = new Ouder();
            $this->ouder->gezin = Ouder::getUser()->gezin;
        }

        // check of vanalles is geset
        if (isset(
            $_POST['ouder-titel'],
            $_POST['ouder-voornaam'],
            $_POST['ouder-achternaam'],
            $_POST['ouder-adres'],
            $_POST['ouder-gemeente'],
            $_POST['ouder-postcode'],
            $_POST['ouder-gsm'],
            $_POST['ouder-telefoon'],
            $_POST['ouder-email']
        )) {
            $data = array(
                'titel' => $_POST['ouder-titel'],
                'firstname' => $_POST['ouder-voornaam'],
                'lastname' => $_POST['ouder-achternaam'],
                'adres' => $_POST['ouder-adres'],
                'gemeente' => $_POST['ouder-gemeente'],
                'postcode' => $_POST['ouder-postcode'],
                'telefoon' => $_POST['ouder-telefoon'],
                'phone' => $_POST['ouder-gsm'],
                'mail' => $_POST['ouder-email']
            );

            // Controleren en errors setten
            
            $errors = $this->ouder->setProperties($data);
            if (count($errors) == 0) {
                if ($this->ouder->save()) {
                    $success = true;

                    if ($new) {
                        $this->ouder->sendCreatedMail(User::getUser());
                    }
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                } else {
                    $errors[] = 'Fout bij opslaan ('.Database::getDb()->error.'), neem contact op met de webmaster.';
                }
            }

        }

        return Template::render('leden/ouder-aanpassen', array(
            'new' => $new,
            'id' => $id,
            'ouder' => $data,
            'success' => $success,
            'errors' => $errors,
            'titels' => Ouder::$titels,
        ));
    }
}