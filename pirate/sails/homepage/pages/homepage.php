<?php
namespace Pirate\Sails\Homepage\Pages;

use Pirate\Wheel\Block;
use Pirate\Sails\Files\Models\Album;
use Pirate\Sails\Files\Models\Image;
use Pirate\Sails\Homepage\Models\Slideshow;
use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

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

        

        return Template::render('pages/homepage/homepage', array(
            'menu' => array('transparent' => true),
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            
            'slideshows' => Slideshow::getSlideshows(), 
            /*array(
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}
