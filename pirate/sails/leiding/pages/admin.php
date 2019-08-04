<?php
namespace Pirate\Sail\Leiding\Pages;

use Pirate\Model\Leiding\Leiding;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Admin extends Page
{
    private $adminPage = null;
    private $selected = '';

    public function __construct($adminPage, $selected)
    {
        $this->adminPage = $adminPage;
        $this->selected = $selected;
    }

    public function customHeaders()
    {
        return $this->adminPage->customHeaders();
    }

    public function getStatusCode()
    {
        return $this->adminPage->getStatusCode();
    }

    public function getContent()
    {
        $content = $this->adminPage->getContent();
        $layout = $this->adminPage->hasOwnLayout();

        if ($layout) {
            return $content;
        }
        return Template::render('pages/leiding/admin', array(
            'content' => $content,
            'head' => $this->adminPage->getHead(),
            'admin' => array(
                'selected' => $this->selected,
            ),
        ));
    }
}
