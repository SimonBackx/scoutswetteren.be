<?php
namespace Pirate\Model\Leden;
use Pirate\Model\Model;
use Pirate\Model\Validating\Validator;
use Pirate\Model\Leden\Gezin;
use Pirate\Model\Leden\Lid;

class Afrekening extends Model {
    public $id;
    public $betaald_cash;
    public $betaald_overschrijving;
    public $totaal;

    function __construct($row = array()) {
        if (count($row) == 0) {
            return;
        }

        $this->id = $row['afrekening_id'];

        $this->betaald_cash = $row['betaald_cash'];
        $this->betaald_overschrijving = $row['betaald_overschrijving'];
        $this->totaal = $row['totaal'];
    }

}