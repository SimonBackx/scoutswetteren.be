<?php
namespace Pirate\Sails\Homepage;

use Pirate\Sails\Homepage\Models\Slideshow;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Wheel\AdminRoute;

class HomepageAdminRouter extends AdminRoute
{

    public function doMatch($url, $parts)
    {
        if (!Leiding::hasPermission('redacteur')) {
            return false;
        }

        if ($result = $this->match($parts, '/slideshows/edit/@id', ['id' => 'string'])) {
            $slideshow = Slideshow::getById($result->params->id);
            if (!isset($slideshow)) {
                return false;
            }
            $this->setPage(new Admin\EditSlideshow($slideshow));
            return true;
        }

        if ($result = $this->match($parts, '/slideshows/delete/@id', ['id' => 'string'])) {
            $slideshow = Slideshow::getById($result->params->id);
            if (!isset($slideshow)) {
                return false;
            }
            $this->setPage(new Admin\DeleteSlideshow($slideshow));
            return true;
        }

        if ($result = $this->match($parts, '/slideshows/edit')) {
            $this->setPage(new Admin\EditSlideshow(null));
            return true;
        }

        if ($result = $this->match($parts, '/slideshows')) {
            $this->setPage(new Admin\OverviewSlideshow());
            return true;
        }

    }

}
