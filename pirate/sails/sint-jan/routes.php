<?php
namespace Pirate\Sails\SintJan;

use Pirate\Wheel\Route;

class SintJanRouter extends Route
{
    private $adminPage = null;

    public function doMatch($url, $parts)
    {
        if ($result = $this->match($parts, '/info', [])) {
            $this->setPage(new Pages\Info\Algemeen());
            return true;
        }
        return false;
    }

}
