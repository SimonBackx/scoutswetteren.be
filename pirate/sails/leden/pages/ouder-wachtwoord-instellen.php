<?php
namespace Pirate\Sail\Leden\Pages;

use Pirate\Mail\Mail;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Users\User;
use Pirate\Page\Page;
use Pirate\Template\Template;

class OuderWachtwoordInstellen extends Page
{
    private $ouder = null;

    public function __construct($ouder)
    {
        $this->ouder = $ouder;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
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
                        header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                        return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders";
                    } else {
                        $errors[] = 'Fout bij versturen, neem contact op met de webmaster.';
                    }
                }

            }
        }

        return Template::render('leden/ouder-wachtwoord-instellen', array(
            'ouder' => $data,
            'errors' => $errors,
        ));
    }
}
