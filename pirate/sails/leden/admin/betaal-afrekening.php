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

class BetaalAfrekening extends Page {
    private $afrekening;

    function __construct(Afrekening $afrekening) {
        $this->afrekening = $afrekening;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $prijs = $this->afrekening->getNogTeBetalen();
        $errors = array();

        if (isset($_POST['price'])) {
            $prijs = $_POST['price'];
            $cash = false;
            if (isset($_POST['cash'])) {
                $cash = true;
            }
            
            if ($this->afrekening->betaalMetOverschrijving($prijs, $message, $errors, $cash)) {
                return Template::render('leden/admin/betaal-afrekening', array(
                    'afrekening' => $this->afrekening,
                    'message' => $message,
                    'success' => true
                ));
            }
        }

        return Template::render('leden/admin/betaal-afrekening', array(
            'afrekening' => $this->afrekening,
            'errors' => $errors,
            'prijs' => $prijs,
            'success' => false
        ));
    }
}