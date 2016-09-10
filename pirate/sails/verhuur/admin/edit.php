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

        return 'wip';

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
            'huur' => ''
        );

        $data_checkbox = array(
            'ligt_vast' => false,
            'contract_ondertekend' => false,
            'waarborg_betaald' => false,
            'huur_betaald' => false,
            'waarborg_ingetrokken' => false
        );

        if (!is_null($this->id)) {
            $event = Event::getEvent($this->id);
            if (!is_null($event)) {
                $new = false;

                $data = array(
                    'name' => $event->name,
                    'startdate' => $event->startdate->format('d-m-Y'),
                    'enddate' => $event->enddate->format('d-m-Y'),
                    'overnachting' => ($event->startdate->format('d-m-Y') != $event->enddate->format('d-m-Y')),
                    'location' => $event->location,
                    'endlocation' => $event->endlocation,
                    'group' => $event->group,
                    'starttime' => $event->startdate->format('H:i'),
                    'endtime' => $event->enddate->format('H:i'),
                );

                $data['id'] = $event->id;

                if (is_null($event->location)) {
                    $data['location'] = Event::$defaultLocation;
                }
                if (is_null($event->endlocation)) {
                    $data['endlocation'] = Event::$defaultLocation;
                }

            } else {
                $event = new Event();
            }
        } else {
           $event = new Event();
        }

         $allset = true;
        foreach ($data as $key => $value) {
            if ($key == 'contract_nummer' || $key == 'goedgekeurd')
                continue;

            if (!isset($_POST[$key])) {
                $allset = false;
                break;
            }
            
            $data[$key] = $_POST[$key];
        }

        foreach ($data_checkbox as $key => $value) {
            if (!isset($_POST[$key])) {
                $data_checkbox[$key] = 
            }
            
            $data[$key] = $_POST[$key];
        }

        // Als alles geset is
        if ($allset) {
            if (isset($_POST['overnachting'])) {
                $data['overnachting'] = true;
            }

            // Nu één voor één controleren
            $errors = $event->setProperties($data);

            if (count($errors) == 0) {
                if ($event->save()) {
                    $success = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/maandplanning");
                }
                else
                    $errors[] = 'Probleem bij opslaan';
            }
        }

        return Template::render('maandplanning/admin/edit', array(
            'new' => $new,
            'event' => $data,
            'errors' => $errors,
            'groups' => Event::$groups,
            'default_locatie' => Event::$defaultLocation,
            'default_start_hour' => Event::getDefaultStartHour(),
            'default_end_hour' => Event::getDefaultEndHour(),
            'success' => $success
        ));
    }
}