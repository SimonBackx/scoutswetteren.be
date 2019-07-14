<?php
namespace Pirate\Sail\Verhuur\Pages;

use Pirate\Block\Block;
use Pirate\Classes\Environment\Localization;
use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;
use Pirate\Model\Verhuur\Reservatie;
use Pirate\Page\Page;
use Pirate\Template\Template;

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
            'max_gebouw' => Reservatie::$max_gebouw,
            'max_tenten' => Reservatie::$max_tenten,
            'prijzen' => Reservatie::getPrijzenString(),
            'waarborg_weekend' => Reservatie::$waarborg_weekend,
            'waarborg_kamp' => Reservatie::$waarborg_kamp,
            'prijs_tent_dag' => Reservatie::$prijs_tent_dag,
            'prijs_tent_persoon' => Reservatie::$prijs_tent_persoon,
            'kalender' => $kalender,
            'images' => $images,
            'album' => $album,
        ));
    }
}
