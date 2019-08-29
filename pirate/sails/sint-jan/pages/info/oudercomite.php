<?php
namespace Pirate\Sails\SintJan\Pages\Info;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Oudercomite extends Page
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

        return Template::render('pages/info/oudercomite', array(

        ));
    }
}
