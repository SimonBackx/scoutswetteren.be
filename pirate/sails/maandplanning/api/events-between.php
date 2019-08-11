<?php
namespace Pirate\Sails\Maandplanning\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

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
        
        return Block::getBlock('Maandplanning', 'Kalender')->getEvents($this->start, $this->end);
    }
}