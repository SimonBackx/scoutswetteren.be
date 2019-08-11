<?php
namespace Pirate\Sails\Photos\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Files\Models\Album;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class SetTitle extends Page {
    private $image = null;

    function __construct(Image $image = null) {
        $this->image = $image;
    }

    function getStatusCode() {        
        if (!isset($this->image) || !isset($_POST["t"])) {
            return 404;
        }

        $this->image->image_title = $_POST["t"];

        if ($this->image->save()) {
            return 200;
        }
        return 400;
    }

    function getContent() {
        return "";
    }
}