<?php
namespace Pirate\Sails\Photos\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\Album;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class DeletePhoto extends Page {
    private $errors = array();
    private $image = null;

    function __construct(Image $image = null) {
        $this->image = $image;
    }

    function getStatusCode() {
        if (!isset($this->image)) {
            return 404;
        }

        if ($this->image->delete($this->errors)) {
            return 200;
        }
        return 400;
    }

    function getContent() {
        return json_encode($this->errors);
    }
}