<?php
namespace Pirate\Wheel;

use Pirate\Wheel\Template;

class Page301 extends Page
{
    public function getStatusCode()
    {
        return 301;
    }

    public function getContent()
    {
        return Template::render('pages/errors/301');
    }
}

// Temp
class Page302 extends Page
{
    public function getStatusCode()
    {
        return 302;
    }

    public function getContent()
    {
        return Template::render('pages/errors/302');
    }
}
