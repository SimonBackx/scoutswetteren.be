<?php
namespace Pirate\Sails\SintJan\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Takken extends Page
{
    public $tak;

    public function __construct($tak)
    {
        $this->tak = $tak;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('pages/takken/' . $this->tak, array());
    }
}
