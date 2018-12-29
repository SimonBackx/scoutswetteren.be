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

class OuderWachtwoordInstellen extends Page {
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

        if ($this->ouder->user->hasPassword()) {
            $errors[] = "Deze ouder heeft al een wachtwoord ingesteld";
        } elseif (empty($this->ouder->user->mail)) {
                $errors[] = "Deze ouder heeft nog geen e-mailadres ingesteld. Stel deze eerst in.";
        } else {
            // check of vanalles is geset
            if (isset(
                $_POST['confirm']
            )) {
                if (count($errors) == 0) {
                    if ($this->ouder->sendCreatedMail(User::getUser())) {
                        $success = true;
                        header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                        return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                    } else {
                        $errors[] = 'Fout bij versturen, neem contact op met de webmaster.';
                    }
                }

            }
        }

        return Template::render('leden/ouder-wachtwoord-instellen', array(
            'ouder' => $data,
            'errors' => $errors
        ));
    }
}