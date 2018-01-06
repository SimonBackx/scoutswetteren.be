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
                    'title' => 'Prettige feestdagen en gelukkig 2018!',
                    'text' => 'Geniet van de feesten en alvast een gelukkig 2018 gewenst van alle leiding en het oudercomité!',
                ),

                array(
                    'title' => 'Kerstwandeling',
                    'text' => 'Kom naar onze kerstwandeling op 15 december. Inschrijven kan via kerstwandeling@scoutswetteren.be, maar lees eerst bijhorende brief!',
                    'button' => array('text' => 'Meer info', 'url' => 'https://files.scoutswetteren.be/download/brief-kerstwandeling-2017.pdf')
                ),

                array(
                    'title' => 'Winter is coming',
                    'text' => 'Kom naar onze Kerstwandeling op 15 december en ons Winterfeest op 25 februari (12u)! Info volgt.'
                ),

                array(
                    'title' => 'Tabula Rasa op 28/10',
                    'text' => 'Onze scoutsfuif vindt plaats op 28/10. Affiches verkrijgbaar bij de leiding.'
                ),


                array(
                    'title' => 'Geen scouts op 1/10',
                    'text' => 'Geen scouts op zondag 1/10. De leiding gaat op planningsweekend van 29 september tot 1 oktober om het scoutsjaar voor te bereiden.'
                ),

                array(
                    'title' => 'Startdag op 10/09',
                    'text' => 'Iedereen welkom van 14u tot 17u, ook nieuwe leden! Kom ook zeker naar onze streekbieravond op zaterdag 9/09 met diavoorstelling van de kampen.',
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
            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}