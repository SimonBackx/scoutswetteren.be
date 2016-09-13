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

class BetaalInschrijving extends Page {
    private $lid;

    function __construct(Lid $lid) {
        $this->lid = $lid;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $errors = array();
        $inschrijving = $this->lid->inschrijving;

        $te_betalen = $inschrijving->getNogTeBetalen();
        $prijs = $te_betalen;

        if (isset($_POST['price'])) {
            $prijs = $_POST['price'];
            
            if ($inschrijving->betaal($prijs, $message, $errors)) {
                return Template::render('leden/admin/betaal-inschrijving', array(
                    'inschrijving' => $inschrijving,
                    'message' => $message,
                    'success' => true
                ));
            }
        }

        return Template::render('leden/admin/betaal-inschrijving', array(
            'inschrijving' => $inschrijving,
            'errors' => $errors,
            'prijs' => $prijs,
            'success' => false,
            'te_betalen' => $te_betalen
        ));
    }
}