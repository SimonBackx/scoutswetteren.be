<?php
namespace Pirate\Sails\Dependencies\Admin;

use Pirate\Wheel\Dependencies;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Overview extends Page
{
    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $dependencies = new Dependencies();
        $output = array();
        $dependencies->check($output);

        return Template::render('admin/dependencies/overview', array(
            'output' => $output,
        ));
    }
}
