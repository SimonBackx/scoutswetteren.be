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
                    'title' => 'Startdag op 10/09',
                    'text' => 'Iedereen welkom van 14u tot 17u, ook nieuwe leden! Kom ook zeker naar onze streekbieravond op 9/09 met diavoorstelling van de kampen.',
                    'button' => array('text' => 'Meer info', 'url' => '/blog/2017/08/15/startdag-2017')
                ),

                array(
                    'title' => 'Kampdata 2017',
                    'text' => '(Jong)giverkamp is van 1 tot 11 augustus, kapoenenkamp van 1 tot 5 augustus en wouterkamp van 5 tot 11 augustus.',
                    'button' => array('text' => 'Meer info', 'url' => '/info')
                ),

                array(
                    'title' => 'Algemene vergadering VZW',
                    'text' => 'Op donderdag 20 april om 20u vindt de algemene vergadering van de VZW plaats in onze scoutslokalen. Alle ouders en leiding zijn welkom.',
                    'button' => array('text' => 'Meer info', 'url' => 'https://files.scoutswetteren.be/download/algemene-vergadering-vzw-2017.pdf')
                ),

                array(
                    'title' => 'WI WA Wafelbak 2017!',
                    'text' => 'Op zondag 26 maart gaan we weer heerlijke wafeltjes verkopen! Plaats op voorhand uw bestelling + We zoeken nog naar behulpzame ouders die ons willen helpen bakken.',
                    'button' => array('text' => 'Meer info', 'url' => '/blog/2017/03/26/wafelbak-2017'),
                    'extra_button' => array('text' => 'Bestellen', 'url' => 'https://files.scoutswetteren.be/manueel/wafels-bestellen.pdf')
                ),

                array(
                    'title' => 'Winterfeest: 12 februari',
                    'text' => 'We nodigen iedereen uit voor ons jaarlijks eetfestijn met dit jaar als thema: "Prinsjes Got Talent". Inschrijven is verplicht.',
                    'button' => array('text' => 'Inschrijven + meer info', 'url' => '/blog/2017/01/09/winterfeest-2017')
                ),

                array(
                    'title' => 'Kerstactiviteit 16 december',
                    'text' => 'Zoals ieder jaar organiseren we dan onze fameuze fakkel- en sneukeltocht met na de tocht de mogelijkheid om iets te drinken op het scoutsterrein. Inschrijven verplicht.',
                    'button' => array('text' => 'Inschrijven + meer info', 'url' => '/blog/2016/11/27/kerstactiviteit-2016')
                ),
                array(
                    'title' => 'Gloednieuwe foto pagina',
                    'text' => 'We hebben de foto pagina afgewerkt. Daar vind je de recentste foto\'s van je lieve spruit.',
                    'button' => array('text' => 'Foto\'s bekijken', 'url' => '/fotos')
                ),
                array(
                    'title' => 'Wafelbak wordt uitgesteld',
                    'text' => 'Door omstandigheden wordt de wafelbak uitgesteld naar het 2e semester. De werkvergadering voor de ouders en leiding gaat wel nog steeds door op 12 november. Zondag 13 november is het dus gewoon scouts.'//,
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