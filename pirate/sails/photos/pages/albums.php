<?php
namespace Pirate\Sail\Photos\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Album;

class Albums extends Page {
    private $tak = null;
    private $page = 1;

    function __construct($tak = null, $page = 1) {
        $this->tak = $tak;
        $this->page = $page;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $albums = Album::getAlbums($this->tak, $this->page, true);
        $year = date("Y");

        $albums_data = array();

        foreach ($albums as $album) {
            $y = $album->date_taken->format('Y');
            if (!isset($albums_data[$y])) {
                $albums_data[$y] = array();
            }
            $albums_data[$y][] = $album;
        }

        return Template::render('photos/albums', array(
            'albums' => $albums_data,
            'year' => $year,
            'tak' => $this->tak
        ));
    }
}