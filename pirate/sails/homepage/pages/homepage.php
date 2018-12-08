<?php
namespace Pirate\Sail\Homepage\Pages;
use Pirate\Block\Block;
use Pirate\Page\Page;

// temp
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
                /*array(
                    'title' => 'Soepverkoop Jin',
                    'text' => "Op zondag 18 november verkoopt de Jin zelfgemaakte pompoepsoep. Bestellen kan via jin@scoutswetteren.be",
                ),*/

                array(
                    'title' => 'Kerstactiviteit en jincafé',
                    'text' => "Kom op vrijdagavond 21 december naar onze jaarlijkse kerstactiviteit. Hierna zijn jullie welkom op het jincafé om onze Jin-tak te steunen! Meer info volgt.",
                )

            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}