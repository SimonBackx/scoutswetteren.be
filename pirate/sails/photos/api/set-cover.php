<?php
namespace Pirate\Sails\Photos\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\Album;

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