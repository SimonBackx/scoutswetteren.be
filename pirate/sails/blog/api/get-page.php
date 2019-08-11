<?php
namespace Pirate\Sails\Blog\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

// start = inclusive Y-m-d
// end = exclusive Y-m-d
class GetPage extends Page {
    private $page;

    function __construct($page) {
        $this->page = $page;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Block ophalen van maandplanning sail
        return Block::getBlock('Blog', 'Overview')->getArticles($this->page);
    }
}