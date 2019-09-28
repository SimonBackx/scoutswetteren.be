<?php
namespace Pirate\Sails\Verhuur\Pages;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Environment\Classes\Localization;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Verhuur\Models\Reservatie;
use Pirate\Wheel\Block;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Verhuur extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $verhuurkalender_block = Block::getBlock('Verhuur', 'Verhuurkalender');
        $verhuurkalender_block->getMonth($year, $month);

        $kalender = $verhuurkalender_block->getContent();

        $album = Album::getHiddenAlbum("verhuur");
        $images = Image::getImagesFromAlbum($album->id);

        return Template::render('pages/verhuur/verhuur', array(
            'calendar' => array(
                'month' => ucfirst(Localization::getMonth($month)),
                'data_year' => $year,
                'data_month' => $month,
            ),
            'calculate_huurprijs' => Reservatie::js_calculateHuur(),
            'calculate_borg' => Reservatie::js_calculateBorg(),
            'max_gebouw' => Environment::getSetting('verhuur.max_gebouw', 0),
            'max_tenten' => Environment::getSetting('verhuur.max_tenten', 0),
            'prijzen' => Reservatie::getPrijzenString(),
            'waarborg_weekend' => Environment::getSetting('verhuur.waarborg_weekend', 0),
            'waarborg_kamp' => Environment::getSetting('verhuur.waarborg_kamp', 0),
            'prijs_tent_nacht' => Environment::getSetting('verhuur.prijs_tent_nacht', 0),
            'prijs_tent_persoon' => Environment::getSetting('verhuur.prijs_tent_persoon', 0),
            'tenten_min_nachten' => Environment::getSetting('verhuur.tenten_min_nachten', 0),
            'prijs_inbegrepen_personen' => Environment::getSetting('verhuur.prijs_inbegrepen_personen', 0),
            'prijs_extra_persoon_gebouw' => Environment::getSetting('verhuur.prijs_extra_persoon_gebouw', 0),
            'kalender' => $kalender,
            'images' => $images,
            'album' => $album,
        ));
    }
}
