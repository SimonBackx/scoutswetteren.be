<?php
namespace Pirate\Sail\Verhuur\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class Kalender extends Page {
    private $jaar;
    private $maand;

    function __construct($jaar, $maand) {
        $this->jaar = $jaar;
        $this->maand = $maand;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        
        return Block::getBlock('Verhuur', 'Verhuurkalender')->getForMonth($this->jaar, $this->maand);
    }
}