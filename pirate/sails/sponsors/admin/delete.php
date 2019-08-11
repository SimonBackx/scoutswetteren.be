<?php
namespace Pirate\Sails\Sponsors\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Sponsors\Models\Sponsor;

class Delete extends Page {
    private $sponsor = null;

    function __construct($sponsor = null) {
        $this->sponsor = $sponsor;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->sponsor->delete();
            header("Location: https://".$_SERVER['SERVER_NAME']."/admin/sponsors");
        }

        return Template::render('admin/sponsors/delete', array(
            'sponsor' => $this->sponsor,
            'success' => $success
        ));
    }
}