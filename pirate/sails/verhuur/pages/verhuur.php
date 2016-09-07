<?php
namespace Pirate\Sail\Verhuur\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Verhuur extends Page {

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $kalender = Block::getBlock('Verhuur', 'Verhuurkalender')->getContent();

        return Template::render('verhuur/verhuur', array(
            'kalender' => $kalender,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}