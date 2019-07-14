<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;

class ViewLid extends Page {
    private $lid;

    function __construct(Lid $lid) {
        $this->lid = $lid;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $ouders = Ouder::getOudersForGezin($this->lid->gezin->id);

        return Template::render('admin/leden/lid', array(
            'lid' => $this->lid,
            'ouders' => $ouders
        ));
    }
}