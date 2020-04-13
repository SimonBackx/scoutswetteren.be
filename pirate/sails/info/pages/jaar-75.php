<?php
namespace Pirate\Sails\Info\Pages;

use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Leden\Models\Lid;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Jaar75 extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('pages/info/75-jaar', array());
    }
}
