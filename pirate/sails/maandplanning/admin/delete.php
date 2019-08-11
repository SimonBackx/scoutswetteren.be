<?php
namespace Pirate\Sails\Maandplanning\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Maandplanning\Models\Event;
use Pirate\Sails\Leiding\Models\Leiding;

class Delete extends Page {
    private $id = null;

    function __construct($id = null) {
        $this->id = $id;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        
        $fail = false;
        $success = false;

        $name = '';
        $id = '';

        if (!is_null($this->id)) {
            $event = Event::getEvent($this->id);
            if (!is_null($event)) {
                $name = $event->name;
                $id = $event->id;

            } else {
                $fail = true;
                header("Location: https://".$_SERVER['SERVER_NAME']."/admin/maandplanning");
            }
        } else {
            // Bestaat niet!
           $fail = true;
        }

        if (!$fail && isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $event->delete();
            header("Location: https://".$_SERVER['SERVER_NAME']."/admin/maandplanning");
        }

        return Template::render('admin/maandplanning/delete', array(
            'activiteit_naam' => $name,
            'activiteit_id' => $id,
            'success' => $success,
            'fail' => $fail
        ));
    }
}