<?php
namespace Pirate\Sails\SintJan\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Privacy extends Page
{
    public function __construct()
    {
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('pages/homepage/privacy', array());
    }
}
