<?php
namespace Pirate\Sail\Leiding\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Leiding\Leiding;

class Admin extends Page {
    private $adminPage = null;
    private $selected = '';

    function __construct($adminPage, $selected) {
        $this->adminPage = $adminPage;
        $this->selected = $selected;
    }

    function customHeaders() {
        return $this->adminPage->customHeaders();
    }

    function getStatusCode() {
        return $this->adminPage->getStatusCode();
    }

    function getContent() {
        $content = $this->adminPage->getContent();
        $layout = $this->adminPage->hasOwnLayout();

        if ($layout) {
            return $content;
        }
        return Template::render('admin/leiding', array(
            'content' => $content,
            'head' => $this->adminPage->getHead(),
            'admin' => array(
                'selected' => $this->selected
            )
        ));
    }
}