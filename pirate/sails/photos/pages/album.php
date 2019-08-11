<?php
namespace Pirate\Sails\Photos\Pages;

use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class AlbumOverview extends Page
{
    private $album = null;

    public function __construct(Album $album)
    {
        $this->album = $album;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $images = Image::getImagesFromAlbum($this->album->id);

        return Template::render('pages/photos/album', array(
            'album' => $this->album,
            'images' => $images,
        ));
    }
}
