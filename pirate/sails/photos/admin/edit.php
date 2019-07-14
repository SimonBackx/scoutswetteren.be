<?php
namespace Pirate\Sail\Photos\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Maandplanning\Event;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Files\File;
use Pirate\Model\Leden\Inschrijving;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

class Edit extends Page {
    private $album;

    function __construct(Album $album) {
        $this->album = $album;
    }

    function getStatusCode() {
        return 200;
    }

    function getHead() {
        return '<link rel="stylesheet" href="/css/photoswipe.css"><script src="/js/photoswipe.min.js"></script>';
    }

    function getContent() {
        $images = Image::getImagesFromAlbum($this->album->id);
        $errors = array();
        $success = false;
        $data = array(
            'album_name' => $this->album->name,
            'group' => $this->album->group,
            'id' => $this->album->id
        );

        if (isset($_POST['group'], $_POST['album_name'])) {
            $data['album_name'] = $_POST['album_name'];
            $data['group'] = $_POST['group'];

            $success = false;
            if ($this->album->setProperties($data, $errors)) {
                if ($this->album->save()) {
                    $success = true;
                } else {
                    $errors[] = 'Er ging iets mis bij het opslaan';
                }
            }
        }

        // Alle albumloze afbeeldingen ophalen
        return Template::render('admin/photos/album', array(
            'data' => $data,
            'album' => $this->album,
            'new' => false,
            'groups' => Album::$groups,
            'max_upload_size' => File::$max_size,
            'errors' => $errors,
            'success' => $success,
            'images' => $images,
            'stats' => $this->album->getFileStatistics(),
        ));
    }
}