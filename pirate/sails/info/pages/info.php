<?php
namespace Pirate\Sail\Info\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Info extends Page {
    private $page = null;

    function __construct($page = null) {
        $this->page = $page;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        if (is_null($this->page)) {
            return Template::render('info/info', array(
                'call_to_action' => array(
                    'title' => 'Volg je kapoen',
                    'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                    'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
                )
            ));
        }
        return Template::render('info/'.$this->page, array(
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}