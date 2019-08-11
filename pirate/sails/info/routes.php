<?php
namespace Pirate\Sails\Info;
use Pirate\Wheel\Page;
use Pirate\Wheel\Route;

class InfoRouter extends Route {
    private $adminPage = null;

    function doMatch($url, $parts) {
        if ($url == 'info') {
            return true;
        }
        if (!isset($parts[0]) || $parts[0] != 'info') {
            return false;
        }

        if (count($parts) == 2) {
            if ($parts[1] == 'kapoenen') {
                return true;
            }
            if ($parts[1] == 'wouters') {
                return true;
            }
            if ($parts[1] == 'jonggivers') {
                return true;
            }
            if ($parts[1] == 'givers') {
                return true;
            }
            if ($parts[1] == 'jin') {
                return true;
            }
        }
       
        return false;
    }

    function getPage($url, $parts) {
        if ($url == 'info') {
            require(__DIR__.'/pages/info.php');
            return new Pages\Info();
        }
        
        require(__DIR__.'/pages/info.php');
        return new Pages\Info($parts[1]);
    }
}