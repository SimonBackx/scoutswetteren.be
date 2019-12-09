<?php
namespace Pirate\Sails\Sponsors;

use Pirate\Wheel\Route;

class SponsorsRouter extends Route
{
    public function doMatch($url, $parts)
    {
        if ($this->match($parts, '/sponsors')) {
            $this->setPage(new Pages\Sponsors());
            return true;
        }

        return false;
    }
}
