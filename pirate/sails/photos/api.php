<?php
namespace Pirate\Sail\Photos;
use Pirate\Page\Page;
use Pirate\Route\Route;

class PhotosApiRouter extends Route {
    function doMatch($url, $parts) {
        if ($parts[0] == 'upload') {
            return true;
        }
        return false;
    }

    function getPage($url, $parts) {
        require(__DIR__.'/api/upload-photo.php');
        return new Api\UploadPhoto();
    }
}