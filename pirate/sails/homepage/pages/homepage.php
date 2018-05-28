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
                array(
                    'title' => 'De kampen komen eraan!',
                    'text' => 'Kapoenenkamp: 1 - 5 augustus, Wouterkamp: 5 - 11 augustus, (Jong)giverskamp: 1 - 11 augustus. Info volgt via e-mail en huisbezoeken.',
                ),

                array(
                    'title' => 'Groepsreis op paasmaandag ',
                    'text' => 'Op 2 april is het weer groepsreis! We gaan zwemmen in S&R Rozenbroeken. Inschrijvingen zijn intussen gesloten.',
                ),

                array(
                    'title' => 'Inschrijven winterfeest ⛄️',
                    'text' => 'Op 25 februari is het weer ons jaarlijks eetfestijn. Inschrijven kan via de knop hieronder.',
                    'button' => array('text' => 'Inschrijven', 'url' => '/inschrijven-winterfeest'),
                    'extra_button' => array('text' => 'Meer info', 'url' => 'https://files.scoutswetteren.be/download/brief-winterfeest-2018.pdf'),
                ),

                array(
                    'title' => 'Prettige feestdagen en gelukkig 2018!',
                    'text' => 'Geniet van de feesten en alvast een gelukkig 2018 gewenst van alle leiding en het oudercomité!',
                ),

                array(
                    'title' => 'Kerstwandeling',
                    'text' => 'Kom naar onze kerstwandeling op 15 december. Inschrijven kan via kerstwandeling@scoutswetteren.be, maar lees eerst bijhorende brief!',
                    'button' => array('text' => 'Meer info', 'url' => 'https://files.scoutswetteren.be/download/brief-kerstwandeling-2017.pdf'),
                ),
            ),
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/'),
            ),
        ));
    }
}