<?php
namespace Pirate\Sail\Photos\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
use Pirate\Model\Leiding\Leiding;

class Upload extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('photos/admin/upload', array(
        ));
    }
}