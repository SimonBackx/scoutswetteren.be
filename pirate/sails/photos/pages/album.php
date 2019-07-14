<?php
namespace Pirate\Sail\Photos\Pages;

use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;
use Pirate\Page\Page;
use Pirate\Template\Template;

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
