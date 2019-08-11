<?php
namespace Pirate\Sails\Photos\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Files\Models\File;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\Album;

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