<?php
namespace Pirate\Sail\Dependencies\Admin;

use Pirate\Dependency\Dependencies;
use Pirate\Page\Page;
use Pirate\Template\Template;

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
