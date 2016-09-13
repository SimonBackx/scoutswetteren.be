<?php
namespace Pirate\Sail\Verhuur\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;
use Pirate\Model\Leiding\Leiding;

class Edit extends Page {
    private $id = null;

    function __construct($id = null) {
        $this->id = $id;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        $new = true;
        $errors = array();
        $success = false;

        $data = array(
            'contract_nummer' => '',

            'startdatum' => '',
            'einddatum' => '',
            'personen' => 1,
            'personen_tenten' => 0,
            'groep' => '',
            'contact_naam' => '',
            'contact_email' => '',
            'contact_gsm' => '',
            'info' => '',
            'opmerkingen' => '',
            'waarborg' => '',
            'huur' => '',
            'waarborg_ingetrokken' => '',
            'goedgekeurd' => null
        );

        $data_checkbox = array(
            'ligt_vast' => false,
            'contract_ondertekend' => false,
            'waarborg_betaald' => false,
            'huur_betaald' => false,
        );



        if (!is_null($this->id)) {
            $reservatie = Reservatie::getReservatie($this->id);
            if (!is_null($reservatie)) {
                $new = false;

                $data = array(
                    'contract_nummer' => $reservatie->contract_nummer,

                    'startdatum' => $reservatie->startdatum->format('d-m-Y'),
                    'einddatum' => $reservatie->einddatum->format('d-m-Y'),
                    'personen' => $reservatie->personen,
                    'personen_tenten' => $reservatie->personen_tenten,
                    'groep' => $reservatie->groep,
                    'contact_naam' => $reservatie->contact_naam,
                    'contact_email' => $reservatie->contact_email,
                    'contact_gsm' => $reservatie->contact_gsm,
                    'info' => $reservatie->info,
                    'opmerkingen' => $reservatie->opmerkingen,
                    'waarborg' => $reservatie->getWaarborg(),
                    'huur' => $reservatie->getHuur(),
                    'waarborg_ingetrokken' => $reservatie->getWaarborgIngetrokken(),
                    'goedgekeurd' => $reservatie->goedgekeurd
                );

                $data_checkbox = array(
                    'ligt_vast' => $reservatie->ligt_vast,
                    'contract_ondertekend' => $reservatie->contract_ondertekend,
                    'waarborg_betaald' => $reservatie->waarborg_betaald,
                    'huur_betaald' => $reservatie->huur_betaald
                );

                //$data['id'] = $reservatie->id; // irnogen ook toevoegen heirna!


            } else {
                $reservatie = new Reservatie();
            }
        } else {
           $reservatie = new Reservatie();
        }

        $allset = true;
        foreach ($data as $key => $value) {
            if (!isset($_POST[$key])) {
                if ($key == 'contract_nummer')
                    continue;

                $allset = false;
                break;
            }
            
            $data[$key] = $_POST[$key];
        }

        if ($allset) {
            foreach ($data_checkbox as $key => $value) {
                if (!isset($_POST[$key])) {
                    $data_checkbox[$key] = false;
                } else {
                    $data_checkbox[$key] = true;
                }
            }
        }
        $data = array_merge($data, $data_checkbox);

        // Als alles geset is
        if ($allset) {

            if ($data['goedgekeurd'] !== '') {
                $data['goedgekeurd'] = ($data['goedgekeurd'] == 1);
            } else {
                $data['goedgekeurd'] = null;
            }

            // Nu één voor één controleren
            $errors = $reservatie->setProperties($data);

            if (count($errors) == 0) {
                if ($reservatie->save()) {
                    $success = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/verhuur");
                }
                else
                    $errors[] = 'Probleem bij opslaan';
            }
        }



        return Template::render('verhuur/admin/edit', array(
            'new' => $new,
            'data' => $data,
            'errors' => $errors,
            'success' => $success
        ));
    }
}