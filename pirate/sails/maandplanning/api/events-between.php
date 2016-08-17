<?php
namespace Pirate\Sail\Maandplanning\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class EventsBetween extends Page {
    private $start;
    private $end;

    function __construct($start, $end) {
        $this->start = $start;
        $this->end = $end;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        
        return Block::getBlock('Maandplanning', 'Kalender')->getEvents();
    }
}