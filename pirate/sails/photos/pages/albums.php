<?php
namespace Pirate\Sails\Photos\Pages;

use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Leden\Models\Inschrijving;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Albums extends Page
{
    private $tak = null;
    private $page = 0;

    public function __construct($tak = null, $page = 0)
    {
        $this->tak = $tak;
        $this->page = $page;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
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

        return Template::render('pages/photos/albums', array(
            'albums' => $albums_data,
            'groups' => Album::getGroups(),
            'tak' => $this->tak,
        ));
    }
}
