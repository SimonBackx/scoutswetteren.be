<?php
namespace Pirate\Sails\Homepage\Admin;

use Pirate\Sails\Homepage\Models\Slideshow;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OverviewSlideshow extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $slideshows = Slideshow::getSlideshows();

        return Template::render('admin/slideshows/overview', array(
            'slideshows' => $slideshows,
        ));
    }
}
