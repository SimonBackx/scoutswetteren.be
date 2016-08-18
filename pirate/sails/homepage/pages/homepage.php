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
        $blog = '';

        return Template::render('homepage', array(
            'title' => 'Titel',
            'description' => 'Beschrijving',
            'maandplanning' => $maandplanning,
            'blog' => $blog,
            'slideshows' => array(
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning en foto\'s vind je nu sneller terug.',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/')
                ),
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning en foto\'s vind je nu sneller terug.',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/'),
                    'extra_button' => array('text' => 'Inschrijven', 'url' => '/inschrijven'),
                ),
                array(
                    'title' => 'Een nieuwe huisstijl!',
                    'text' => 'Met onze nieuwe website en huisstijl kan je vanaf nu ook online inschrijven. En de maandplanning en foto\'s vind je nu sneller terug.',
                    'button' => array('text' => 'Meer lezen', 'url' => '/blog/2016/'),
                    'extra_button' => array('text' => 'Inschrijven', 'url' => '/inschrijven'),
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