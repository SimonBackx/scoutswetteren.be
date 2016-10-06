<?php
namespace Pirate\Sail\Photos;
use Pirate\Page\Page;
use Pirate\Route\Route;

class PhotosAdminRouter extends Route {
    private $id = null;

    function doMatch($url, $parts) {
        if ($url == 'photos/upload') {
            return true;
        }

        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/admin/upload.php');
        return new Admin\Upload();
    }
}