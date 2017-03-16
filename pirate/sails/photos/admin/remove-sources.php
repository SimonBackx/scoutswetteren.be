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

class RemoveSources extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $albums = Album::getAlbums();

        if (!isset($_POST['confirm'])) {
            // Eerst confirmatie vragen voor we alles verwijderen
            
            $albums_with_sources = array();
            $albums_with_zip = array();

            foreach ($albums as $album) {
                if ($album->sources_available) {
                    $albums_with_sources[] = $album;
                }
                if (isset($album->zip_file)) {
                    $albums_with_zip[] = $album;
                }
            }
            return Template::render('photos/admin/remove-sources', array(
                'albums_with_sources' => $albums_with_sources,
                'albums_with_zip' => $albums_with_zip
            ));
        }
        
        // Todo: daadwerkelijk wissen
        // 
        
        return Template::render('photos/admin/remove-sources-done');
    }
}