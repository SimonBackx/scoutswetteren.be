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

class OuderVerwijderen extends Page {
    private $ouder = null;

    function __construct($ouder) {
        $this->ouder = $ouder;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        $data = array();
        $errors = array();

        if (isset($this->ouder)) {
            $new = false;
            $data = $this->ouder->getProperties();
        } else {
            return 'Internal error';
        }

        // check of vanalles is geset
        if (isset(
            $_POST['confirm']
        )) {
            if (count($errors) == 0) {
                if ($this->ouder->delete()) {
                    $success = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                } else {
                    $errors[] = 'Fout bij opslaan ('.Database::getDb()->error.'), neem contact op met de webmaster.';
                }
            }

        }

        return Template::render('leden/ouder-verwijderen', array(
            'ouder' => $data,
            'errors' => $errors,
            'titels' => Ouder::$titels,
        ));
    }
}