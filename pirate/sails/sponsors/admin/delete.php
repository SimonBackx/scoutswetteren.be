<?php
namespace Pirate\Sail\Sponsors\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Sponsors\Sponsor;

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

        return Template::render('sponsors/admin/delete', array(
            'sponsor' => $this->sponsor,
            'success' => $success
        ));
    }
}