<?php
namespace Pirate\Sail\Photos\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;

class AlbumOverview extends Page {
    private $album = null;

    function __construct(Album $album) {
        $this->album = $album;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $images = Image::getImagesFromAlbum($this->album->id);

        return Template::render('photos/album', array(
            'album' => $this->album,
            'images' => $images
        ));
    }
}