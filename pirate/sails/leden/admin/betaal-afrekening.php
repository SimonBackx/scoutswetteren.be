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
                return Template::render('admin/leden/betaal-afrekening', array(
                    'afrekening' => $this->afrekening,
                    'message' => $message,
                    'success' => true
                ));
            }
        }

        return Template::render('admin/leden/betaal-afrekening', array(
            'afrekening' => $this->afrekening,
            'errors' => $errors,
            'prijs' => $prijs,
            'success' => false
        ));
    }
}