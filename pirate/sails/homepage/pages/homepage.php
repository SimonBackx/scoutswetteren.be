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
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            'slideshows' => array(
                array(
                    'title' => 'Wafelbak wordt uitgesteld',
                    'text' => 'Door omstandigheden wordt de wafelbak uitgesteld naar het 2e semester. De werkvergadering voor de ouders en leiding gaat wel nog steeds door op 12 november. Zondag 13 november is het dus gewoon scouts.'//,
                    /*'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/10/15/wafelverkoop-12-november-2016'),
                    'extra_button' => array('text' => 'Bestelformulier', 'url' => '/files/wafelverkoop2016.pdf')*/
                ),
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning vind je nu altijd terug op de startpagina',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/09/09/nieuwe-huisstijl-en-online-inschrijven')
                ),
                array(
                    'title' => 'Startdag 2016',
                    'text' => 'Zondag 11 september vliegen we er weer in vanaf 14 uur! En vrijdag 9 september om 19 uur kan je komen genieten van lekkere streekbieren of frisdranken op onze streekbieravond.'
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