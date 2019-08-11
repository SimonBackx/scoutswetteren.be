<?php
namespace Pirate\Sails\Leden\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Afrekening;

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