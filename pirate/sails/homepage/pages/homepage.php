<?php
namespace Pirate\Sail\Homepage\Pages;

use Pirate\Block\Block;
use Pirate\Model\Files\Album;
use Pirate\Model\Files\Image;
use Pirate\Model\Homepage\Slideshow;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Homepage extends Page
{

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {

        // Block ophalen van maandplanning sail
        $maandplanning = Block::getBlock('Maandplanning', 'Kalender')->getContent();
        $blog = Block::getBlock('Blog', 'Overview')->getContent();

        // Get two latest albums

        $albums = Album::getAlbums(null, 1, false, 2);
        $album_images = [];

        foreach ($albums as $album) {
            $album_images[] = [
                'album' => $album,
                'images' => Image::getImagesFromAlbum($album->id),
            ];
        }

        return Template::render('pages/homepage/homepage', array(
            'menu' => array('transparent' => true),
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            'album_images' => $album_images,
            'slideshows' => Slideshow::getSlideshows(), /*array(
            array(
            'title' => 'Wij zoeken wafelbakkers!',
            'text' => "Op 31/03 organiseren we onze wafelbak. Daarvoor zijn we nog op zoek naar enthousiaste bakkers! Wil jij graag wafeltjes bakken? Laat het ons weten via wafels@scoutswetteren.be",
            "button" => array('text' => 'Meer info', 'url' => 'https://files.scoutswetteren.be/download/wafelbak-algemeen-2019.pdf'),
            ),

            array(
            'title' => 'Schrijf je in voor het Winterfeest!',
            'text' => "Op zondag 24 februari organiseren we ons jaarlijks eetfestijn. Inschrijven kan enkel online.",
            "button" => array('text' => 'Inschrijven', 'url' => '/inschrijvingen/1/inschrijven-voor-winterfeest'),
            "extra_button" => array('text' => 'Meer info', 'url' => '/blog/2019/02/24/winterfeest-2019'),
            ),

            array(
            'title' => 'Kerstactiviteit en jincafé',
            'text' => "Kom op vrijdagavond 21 december naar onze jaarlijkse kerstactiviteit. Hierna zijn jullie welkom op het jincafé om onze Jin-tak te steunen! Meer info volgt.",
            ),

            ),*/
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}
