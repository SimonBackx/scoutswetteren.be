<?php
namespace Pirate\Sails\Homepage\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Privacy extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('pages/homepage/privacy', array());
    }
}
