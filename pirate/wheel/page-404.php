<?php
namespace Pirate\Wheel;

use Pirate\Wheel\Template;

class Page404 extends Page
{
    public function getStatusCode()
    {
        return 404;
    }

    public function getContent()
    {
        return Template::render('pages/errors/404');
    }
}
