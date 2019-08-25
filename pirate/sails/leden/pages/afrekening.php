<?php
namespace Pirate\Sails\Leden\Pages;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class ViewAfrekening extends Page
{
    private $afrekening;

    public function __construct($afrekening)
    {
        $this->afrekening = $afrekening;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        // Kijken of de steekkaart al geset is
        $finished = false;
        if (isset($_GET['klaar'])) {
            $finished = true;
        }
        return Template::render('pages/leden/afrekening', array(
            'afrekening' => $this->afrekening,
            'iban' => Environment::getSetting('bank.iban', 'Onbekend'),
            'bic' => Environment::getSetting('bank.bic', 'Onbekend'),
            'address' => Environment::getSetting('address.street') . ' ' . Environment::getSetting('address.number') . ', ' . Environment::getSetting('address.postalcode') . ' ' . Environment::getSetting('address.city'),
            'finished' => $finished,
        ));
    }
}
