<?php
namespace Pirate\Sail\Photos\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Files\Album;
use Pirate\Model\Leden\Inschrijving;

class Albums extends Page {
    private $tak = null;
    private $page = 0;

    function __construct($tak = null, $page = 0) {
        $this->tak = $tak;
        $this->page = $page;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $albums = Album::getAlbums($this->tak, $this->page, true);

        $albums_data = array();

        foreach ($albums as $album) {
            $y = intval($album->date_taken->format('Y'));
            $m = intval($album->date_taken->format('n'));

            $y = Inschrijving::getScoutsjaarFor($y, $m);
            if (!isset($albums_data[$y])) {
                $albums_data[$y] = array();
            }
            $albums_data[$y][] = $album;
            $album->formatted_date = datetimeToDayMonth($album->date_taken);
        }

        return Template::render('photos/albums', array(
            'albums' => $albums_data,
            'tak' => $this->tak
        ));
    }
}