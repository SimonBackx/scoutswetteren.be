<?php
namespace Pirate\Sail\Leden\Pages;

use Pirate\Database\Database;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Users\User;
use Pirate\Page\Page;
use Pirate\Template\Template;

class OuderAanpassen extends Page
{
    private $ouder = null;

    public function __construct($ouder = null)
    {
        $this->ouder = $ouder;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $new = true;
        $success = false;
        $data = array();
        $errors = array();
        $id = null;

        $cancelable = true;

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
                'mail' => $_POST['ouder-email'],
            );

            // Controleren en errors setten

            $errors = $this->ouder->setProperties($data);
            if (count($errors) == 0) {
                if ($this->ouder->save()) {
                    $success = true;

                    if ($new) {
                        $this->ouder->sendCreatedMail(User::getUser());
                    }
                    header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                    return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders";
                } else {
                    $errors[] = 'Fout bij opslaan (' . Database::getDb()->error . '), neem contact op met de webmaster.';
                }
            }

        }

        return Template::render('pages/leden/ouder-aanpassen', array(
            'new' => $new,
            'id' => $id,
            'ouder' => $data,
            'success' => $success,
            'errors' => $errors,
            'titels' => Ouder::$titels,
            'cancelable' => $cancelable,
        ));
    }
}
