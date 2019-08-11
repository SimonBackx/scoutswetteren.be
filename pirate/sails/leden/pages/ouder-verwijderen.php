<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Wheel\Database;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OuderVerwijderen extends Page
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

        $ouders = Ouder::getOudersForGezin($this->ouder->gezin->id);

        if (count($ouders) <= 1) {
            $errors[] = 'Het is niet mogelijk om de enige ouder in dit gezin te verwijderen';
        } else {

            // check of vanalles is geset
            if (isset(
                $_POST['confirm']
            )) {
                if (count($errors) == 0) {
                    if ($this->ouder->delete()) {
                        $success = true;
                        header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                        return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders";
                    } else {
                        $errors[] = 'Fout bij opslaan (' . Database::getDb()->error . '), neem contact op met de webmaster.';
                    }
                }

            }
        }

        return Template::render('pages/leden/ouder-verwijderen', array(
            'ouder' => $data,
            'errors' => $errors,
            'titels' => Ouder::$titels,
        ));
    }
}
