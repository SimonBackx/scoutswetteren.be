<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Admin extends Page {
    private $adminPage = null;

    function __construct($adminPage) {
        $this->adminPage = $adminPage;
    }

    function getStatusCode() {
        return $this->adminPage->getStatusCode();
    }

    function getContent() {
        return Template::render('leiding/admin', array(
            'content' => $this->adminPage->getContent()
        ));
    }
}