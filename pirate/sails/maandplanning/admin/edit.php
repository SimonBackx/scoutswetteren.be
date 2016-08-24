<?php
namespace Pirate\Sail\Maandplanning\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
use Pirate\Model\Leiding\Leiding;

class Edit extends Page {
    private $id = null;

    function __construct($id) {
        $this->id = $id;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        
        return Template::render('maandplanning/admin/edit', array(
        ));
    }
}