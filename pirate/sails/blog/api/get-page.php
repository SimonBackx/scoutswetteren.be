<?php
namespace Pirate\Sail\Blog\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

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