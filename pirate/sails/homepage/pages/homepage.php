<?php
namespace Pirate\Sail\Homepage\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Homepage extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        
        $maandplanning = Block::getBlock('Maandplanning', 'Kalender')->getContent();
        $blog = Block::getBlock('Blog', 'Overview')->getContent();

        return Template::render('homepage', array(
            'menu' => array('transparent' => true),
            'description' => 'Beschrijving',
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            'slideshows' => array(
                array(
                    'title' => 'Startdag 2016',
                    'text' => 'Zondag 11 september vliegen we er weer in vanaf 14 uur! En vrijdag 9 september om 19 uur kan je komen genieten van lekkere streekbieren of frisdranken op onze streekbieravond.'
                ),
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning vind je nu altijd terug op de startpagina',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/09/09/nieuwe-huisstijl-en-online-inschrijven')
                )
            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}