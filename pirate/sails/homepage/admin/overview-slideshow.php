<?php
namespace Pirate\Sail\Homepage\Admin;

use Pirate\Model\Homepage\Slideshow;
use Pirate\Page\Page;
use Pirate\Template\Template;

class OverviewSlideshow extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $slideshows = Slideshow::getSlideshows();

        return Template::render('slideshows/admin/overview', array(
            'slideshows' => $slideshows,
        ));
    }
}
