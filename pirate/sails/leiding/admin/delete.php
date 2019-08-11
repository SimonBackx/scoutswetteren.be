<?php
namespace Pirate\Sails\Leiding\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;

class Delete extends Page {
    private $leiding = null;

    function __construct($leiding) {
        $this->leiding = $leiding;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $success = false;

        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->leiding->delete();
            header("Location: https://".$_SERVER['SERVER_NAME']."/admin/leiding");
        }

        return Template::render('admin/leiding/delete', array(
            'leiding' => $this->leiding,
            'success' => $success
        ));
    }
}