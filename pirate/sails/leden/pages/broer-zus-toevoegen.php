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

class BroerZusToevoegen extends Page {
    private $lid = null;

    function __construct($lid = null) {
        $this->lid = $lid;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;

        $new = true;
        $fail = false;
        $success = false;

        $lid = array();
        $lid_model = null;
        $errors = array();

        if (isset($this->lid)) {
            $new = false;
            $lid = $this->lid->getProperties();
        }

        // check of vanalles is geset
        if (isset(
            $_POST['lid-voornaam'], 
            $_POST['lid-achternaam'], 
            $_POST['lid-geboortedatum-dag'],
            $_POST['lid-geboortedatum-maand'],
            $_POST['lid-geboortedatum-jaar'],
            $_POST['lid-gsm']
        )) {
            // Hoeveel leden opgegeven?

            $lid = array(
                'voornaam' => $_POST['lid-voornaam'],
                'achternaam' => $_POST['lid-achternaam'],
                'geboortedatum_dag' => $_POST['lid-geboortedatum-dag'],
                'geboortedatum_maand' => $_POST['lid-geboortedatum-maand'],
                'geboortedatum_jaar' => $_POST['lid-geboortedatum-jaar'],
                'gsm' => $_POST['lid-gsm'],
                'geslacht' => ''
            );

            if (isset($_POST['lid-geslacht'])) {
                $lid['geslacht'] = $_POST['lid-geslacht'];
            }

            // Controleren en errors setten
            
            if ($new) {
                $this->lid = new Lid();
            }

            $errors = $this->lid->setProperties($lid);
            if (count($errors) > 0) {
                $fail = true;
            }
            

            if ($fail == false) {
                // Gezin opslaan
                $this->lid->setGezin(Ouder::getUser()->gezin);
                $success = $this->lid->save();
                if ($success == false) {
                     $errors[] = 'Er ging iets mis: '.Database::getDb()->error.' Contacteer de webmaster.';
                } else {
                    // Redirecten hier!!!
                    header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
                    return "Doorverwijzen naar https://".$_SERVER['SERVER_NAME']."/ouders";
                }
            }
        }
        $jaar = Inschrijving::getScoutsjaar();
        $verdeling = Lid::getTakkenVerdeling($jaar);
        $keys = array_keys($verdeling);
        sort($keys);
        $jaren = array();
        for ($i=$keys[0] - 5; $i < $jaar; $i++) { 
            $jaren[] = $i;
        }

        if (!$new && $this->lid->isIngeschreven()) {
            // geen mogelijkheid om tak te wisselen, die ligt al vast
            foreach ($verdeling as $key => $value) {
                $verdeling[$key] = $this->lid->inschrijving->tak;
            }
        }

        return Template::render('leden/broer-zus-toevoegen', array(
            'new' => $new,
            'lid' => $lid,
            'maanden' => $config["months"],
            'jaren' => $jaren,
            'fail' => $fail,
            'success' => $success,
            'errors' => $errors,
            'takken' => json_encode($verdeling)
        ));
    }
}