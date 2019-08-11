<?php
namespace Pirate\Sails\Verhuur\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

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