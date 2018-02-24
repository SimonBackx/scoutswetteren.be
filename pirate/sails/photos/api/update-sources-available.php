<?php
namespace Pirate\Sail\Photos\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

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