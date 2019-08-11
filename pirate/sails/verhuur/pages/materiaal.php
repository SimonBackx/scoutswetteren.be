<?php
namespace Pirate\Sails\Verhuur\Pages;

use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Materiaal extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $album = Album::getHiddenAlbum("materiaalverhuur");
        $images = Image::getImagesFromAlbum($album->id);

        $location = "verhuur/";
        $file_name = "materiaal-prijslijst";
        $extension = "pdf";

        return Template::render('pages/verhuur/materiaal', array(
            'images' => $images,
            'album' => $album,
            'prijslijst_location' => "https://" . str_replace('www.', 'files.', $_SERVER['SERVER_NAME']) . "/" . $location . $file_name . '.' . $extension,
        ));
    }
}
