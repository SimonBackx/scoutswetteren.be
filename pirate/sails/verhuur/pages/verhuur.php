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
        global $config;
        $verhuurkalender_block = Block::getBlock('Verhuur', 'Verhuurkalender');
        $verhuurkalender_block->getMonth($year, $month);

        $kalender = $verhuurkalender_block->getContent();

        return Template::render('verhuur/verhuur', array(
            'calendar' => array(
                'month' => ucfirst($config['months'][$month-1]),
                'data_year' => $year,
                'data_month' => $month
            ),
            'kalender' => $kalender,
            'call_to_action' => array(
                'title' => 'Volg je kapoen',
                'subtitle' => 'Doorheen het jaar en tijdens weekends en kampen posten we geregeld foto\'s en updates op onze facebook pagina.',
                'button' => array('text' => 'Like onze pagina', 'url' => 'https://www.facebook.com/scoutsprinsboudewijn/')
            )
        ));
    }
}