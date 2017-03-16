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

class Export extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (!isset($_POST['confirm'])) {
            
        } else {
            
        }

        $albums = Album::getAlbums();

        // Zip's genereren voor elk album, en die opnieuw samen zippen
        $failed = false;
        foreach ($albums as $album) {
            if ($album->canDownload()) {
                // De sources zijn nog steeds beschikbaar
                $failed = $album->createZip();
                if ($failed) {
                    break;
                }
            }
        }

        return Template::render('photos/admin/overview', array(
            'albums' => $albums,
            'concept_images' => $images
        ));
    }
}