<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Sails\Environment\Classes\Localization;
use Pirate\Sails\Leden\Models\Gezin;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Wheel\Database;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class BroerZusToevoegen extends Page
{
    private $lid = null;

    public function __construct($lid = null)
    {
        $this->lid = $lid;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
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
                'akabe' => isset($_POST['lid-akabe']),
                'geslacht' => '',
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
                    $errors[] = 'Er ging iets mis: ' . Database::getDb()->error . ' Contacteer de webmaster.';
                } else {
                    if ($new) {
                        // Schrijf in + onthoud akabe
                        $force_tak = null;
                        if ($lid['akabe']) {
                            $force_tak = 'akabe';
                        }

                        if ($this->lid->schrijfIn($force_tak)) {
                            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders/verleng-inschrijving");
                            return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders/verleng-inschrijving";
                        } else {
                            $errors[] = 'Er ging iets mis bij het inschrijven. Controleer of je geldige gegevens hebt opgegeven.';
                        }
                    } else {
                        header("Location: https://" . $_SERVER['SERVER_NAME'] . "/ouders");
                        return "Doorverwijzen naar https://" . $_SERVER['SERVER_NAME'] . "/ouders";
                    }
                }
            }
        }
        $jaar = Inschrijving::getScoutsjaar();
        $verdeling = [
            'M' => Lid::getTakkenVerdeling($jaar, 'M', Lid::areLimitsIgnored()),
            'V' => Lid::getTakkenVerdeling($jaar, 'V', Lid::areLimitsIgnored()),
        ];

        // Geboortejaar selectie
        $oldest = min(array_keys($verdeling['M']));
        $jaren = [];
        for ($i = $oldest - 5; $i < $jaar; $i++) {
            $jaren[] = $i;
        }

        // Is dit gewoon een aanpassing na het inschrijven? => tak ligt vast
        if (!$new && $this->lid->isIngeschreven()) {
            $info = Lid::getTakInfo();
            if (isset($info[$this->lid->inschrijving->tak])) {
                $info = $info[$this->lid->inschrijving->tak];

                for ($i = $info['min_year']; $i <= $info['max_year']; $i++) {
                    $verdeling['M'][$i] = $this->lid->inschrijving->tak;
                    $verdeling['V'][$i] = $this->lid->inschrijving->tak;
                }
            }
        }

        return Template::render('pages/leden/broer-zus-toevoegen', array(
            'new' => $new,
            'lid' => $lid,
            'lid_model' => $this->lid,
            'maanden' => Localization::getMonths(),
            'jaren' => $jaren,
            'fail' => $fail,
            'success' => $success,
            'errors' => $errors,
            'takken' => json_encode($verdeling),
            'alle_takken' => Lid::getTakInfo(),
            'limits_ignored' => Lid::areLimitsIgnored(),
            'is_ingeschreven' => !$new && $this->lid->isIngeschreven(),
        ));
    }
}
