<?php
namespace Pirate\Sail\Photos\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class SetCover extends Page {
    private $errors = array();
    private $image = null;

    function __construct(Image $image = null) {
        $this->image = $image;
    }

    function getStatusCode() {
        if (!isset($this->image)) {
            return 404;
        }

        $album = Album::getAlbum($this->image->album);
        if (!isset($album)) {
            return 500;
        }

        $album->cover = $this->image;
        // cover_id moet niet geset wordne, enkel bij deletion

        if ($album->save()) {
            return 200;
        }
        return 500;
    }

    function getContent() {
        return json_encode($this->errors);
    }
}