<?php
namespace Pirate\Sail\Leiding\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

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

        return Template::render('leiding/admin/delete', array(
            'leiding' => $this->leiding,
            'success' => $success
        ));
    }
}