<?php
namespace Pirate\Sail\Leden\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Leden\Afrekening;

class ViewAfrekening extends Page {
    private $afrekening;

    function __construct(Afrekening $afrekening) {
        $this->afrekening = $afrekening;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $ouders = Ouder::getOudersForGezin($this->afrekening->gezin);

        return Template::render('admin/leden/afrekening', array(
            'afrekening' => $this->afrekening,
            'from_inschrijvingen' => !Leiding::hasPermission('financieel'),
            'ouders' => $ouders
        ));
    }
}