<?php
namespace Pirate\Sail\Photos\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class Overview extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $images = Image::getImagesFromAlbum(null);
        $albums = Album::getAlbums();

        return Template::render('admin/photos/overview', array(
            'albums' => $albums,
            'concept_images' => $images
        ));
    }
}