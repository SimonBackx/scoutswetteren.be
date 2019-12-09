<?php
namespace Pirate\Sails\Homepage;

use Pirate\Wheel\Route;

class HomepageRouter extends Route
{
    public function doMatch($url, $parts)
    {
        if ($url == '') {
            return true;
        }

        if ($url == 'privacy') {
            return true;
        }
        return false;
    }

    public function getPage($url, $parts)
    {
        if ($url == 'privacy') {
            require __DIR__ . '/pages/privacy.php';
            return new Pages\Privacy();
        }
        require __DIR__ . '/pages/homepage.php';
        return new Pages\Homepage();
    }
}
