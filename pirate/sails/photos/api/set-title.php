<?php
namespace Pirate\Sail\Photos\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

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