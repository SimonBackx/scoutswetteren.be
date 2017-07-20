<?php
namespace Pirate\Sail\Info\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Inschrijving;

class Info extends Page {
    private $page = null;

    function __construct($page = null) {
        $this->page = $page;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $scoutsjaar = Inschrijving::getScoutsjaar();
        $takkenverdeling = Lid::getTakkenVerdeling($scoutsjaar);
        $jaar_verdeling = array();
        foreach ($takkenverdeling as $jaar => $tak) {
            if (!isset($jaar_verdeling[$tak])) {
                $jaar_verdeling[$tak] = array();
            }
            $jaar_verdeling[$tak][] = $jaar;
        }

        $verdeling_string = array();
        foreach ($jaar_verdeling as $tak => $jaren) {
            $min = min($jaren);
            $max = max($jaren);
            if ($min == $max) {
                $verdeling_string[$tak] = $min;
            } else {
                $verdeling_string[$tak] = $min.' - '.$max;
            }
        }

        if (is_null($this->page)) {
            return Template::render('info/info', array(
                'takkenverdeling' => $verdeling_string,
                'call_to_action' => array(
                    'title' => 'Volg je kapoen',
                    'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                    'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
                )
            ));
        }
        return Template::render('info/'.$this->page, array(
            'takkenverdeling' => $verdeling_string,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}