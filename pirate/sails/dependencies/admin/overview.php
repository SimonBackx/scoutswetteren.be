<?php
namespace Pirate\Sail\Dependencies\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

use Pirate\Dependency\Dependencies;

class Overview extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $dependencies = new Dependencies();
        $output = array();
        $dependencies->check($output);

        return Template::render('dependencies/admin/overview', array(
            'output' => $output
        ));
    }
}