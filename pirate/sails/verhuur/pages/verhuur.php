<?php
namespace Pirate\Sail\Verhuur\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Verhuur\Reservatie;
use Pirate\Model\Files\Image;

class Verhuur extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        global $config;
        $verhuurkalender_block = Block::getBlock('Verhuur', 'Verhuurkalender');
        $verhuurkalender_block->getMonth($year, $month);

        $kalender = $verhuurkalender_block->getContent();

        $images = Image::getImagesFromHiddenAlbum("verhuur");

        return Template::render('verhuur/verhuur', array(
            'calendar' => array(
                'month' => ucfirst($config['months'][$month-1]),
                'data_year' => $year,
                'data_month' => $month
            ),
            'calculate_huurprijs' => Reservatie::js_calculateHuur(),
            'calculate_borg' => Reservatie::js_calculateBorg(),
            'kalender' => $kalender,
            'images' => $images
        ));
    }
}