<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class VZW extends Page
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

        return Template::render('pages/info/vzw', array(

        ));
    }
}
