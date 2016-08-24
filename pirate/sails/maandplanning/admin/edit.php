<?php
namespace Pirate\Sail\Maandplanning\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
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
            'name' => '',
            'startdate' => '',
            'enddate' => '',
            'overnachting' => false,
            'location' => '',
            'endlocation' => '',
            'group' => '',
            'starttime' => '',
            'endtime' => ''
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

            } else {
                $event = new Event();
            }
        } else {
           $event = new Event();
        }

        $allset = true;
        foreach ($data as $key => $value) {
            if ($key == 'overnachting')
                continue;

            if (!isset($_POST[$key])) {
                if ($key == 'group')
                    continue;
                
                $allset = false;
                break;
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
                if ($event->save())
                    $success = true;
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