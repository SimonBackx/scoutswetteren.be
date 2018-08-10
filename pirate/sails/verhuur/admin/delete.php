<?php
namespace Pirate\Sail\Verhuur\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;
use Pirate\Model\Leiding\Leiding;

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
        $message = 'Verwijderen gelukt!';

        if (Leiding::hasPermission('verhuur') || Leiding::hasPermission('oudercomite') || Leiding::hasPermission('groepsleiding')) {
            if (!is_null($this->id)) {
                $reservatie = Reservatie::getReservatie($this->id);
                if (!is_null($reservatie)) {
                    $name = $reservatie->groep;
                    $id = $reservatie->id;

                } else {
                    $fail = true;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/verhuur");
                }
            } else {
                // Bestaat niet!
               $fail = true;
            }

            if (!$fail && isset($_POST['confirm-delete'])) {
                // Echt verwijderen en doorverwijzen
                $success = $reservatie->delete();
                header("Location: https://".$_SERVER['SERVER_NAME']."/admin/verhuur");
            }

        } else {
            $message = 'Geen toestemming om reservaties te verwijderen. Vraag dit aan de verhuur verantwoordelijke.';
            $success = true;
        }

        return Template::render('verhuur/admin/delete', array(
            'naam' => $name,
            'id' => $id,
            'message' => $message,
            'success' => $success,
            'fail' => $fail
        ));
    }
}