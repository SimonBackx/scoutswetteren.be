<?php
namespace Pirate\Sail\Maandplanning\Blocks;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Kalender extends Block {
    function getHead() {
        return '';
    }

    function getContent() {
        return Template::render('kalender', array('test' => 'aardappel'), 'Maandplanning');
    }

}