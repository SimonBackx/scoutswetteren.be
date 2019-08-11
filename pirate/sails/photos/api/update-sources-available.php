<?php
namespace Pirate\Sails\Photos\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\Album;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class UpdateSourcesAvailable extends Page {
    private $errors = array();
    private $album;

    function __construct(Album $album) {
        $this->album = $album;
    }

    function getStatusCode() {
        if (!isset($this->album)) {
            return 500;
        }

        if ($album->updateSourcesAvailable(true)) {
            $errors = array('sources-available' => $album->sources_available);
            return 200;
        } 
        $errors[] = 'Er ging iets mis bij het nakijken van de beschikbaarheid van de bestanden.';
        return 500;
    }

    function getContent() {
        return json_encode($this->errors);
    }
}