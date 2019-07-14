<?php
namespace Pirate\Sail\Homepage\Pages;

use Pirate\Page\Page;
use Pirate\Template\Template;

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
