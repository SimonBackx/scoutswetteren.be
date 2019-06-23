<?php
namespace Pirate\Sail\Verhuur\Pages;

use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;
use Pirate\Page\Page;
use Pirate\Template\Template;

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

        return Template::render('verhuur/materiaal', array(
            'images' => $images,
            'album' => $album,
            'prijslijst_location' => "https://" . str_replace('www.', 'files.', $_SERVER['SERVER_NAME']) . "/" . $location . $file_name . '.' . $extension,
        ));
    }
}
