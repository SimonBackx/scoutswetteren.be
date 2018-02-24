<?php
namespace Pirate\Sail\Photos\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Image;
use Pirate\Model\Files\Album;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class UploadPhoto extends Page {
    private $errors = array();
    private $image = null;
    private $album;

    function __construct(Album $album = null) {
        $this->album = $album;
    }


    function getStatusCode() {
        $image = new Image();
        $sizes = array(
            array('height' => 100),
            array('height' => 300, 'height' => 300),
            array('height' => 600, 'width' => 600),
            array('height' => 720)
        );

        $id = Album::$QUEUE_ID;
        if (isset($this->album)) {
            $id = $this->album->id;
        } else {
            $this->album = Album::getQueueAlbum();
        }

        $image->setAlbum($id);
        if ($image->upload('file', $sizes, $this->errors, $this->album)) {
            $this->image = $image;

            $this->album->onImageAdded($image);
            
            return 200;
        }
        return 400;
    }

    function getContent() {
        if (isset($this->image)) {
            $data = array(
                'sources' => array(),
                'width' => $this->image->sources[0]->width,
                'height' => $this->image->sources[0]->height,
                'id' => $this->image->id
            );

            foreach ($this->image->sources as $source) {
                $data['sources'][] = array('id' => $source->id, 'w' => $source->width, 'h' => $source->height, 'url' => $source->file->getPublicPath());
            }
            return json_encode($data);
        }
        return json_encode($this->errors);
    }
}