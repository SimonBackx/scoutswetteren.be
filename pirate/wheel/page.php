<?php
namespace Pirate\Page;
use Pirate\Template\Template;

class Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return 'getContent method not implemented';
    }
    
    function hasOwnLayout() {
        return false;
    }

    final function execute() {
        http_response_code($this->getStatusCode());
        echo $this->getContent();
    }

    function goodbye() {
        
    }

}

class Page404 extends Page {
    function getStatusCode() {
        return 404;
    }

    function getContent() {
        return Template::render('404');
    }
}

class Page301 extends Page {
    function getStatusCode() {
        return 301;
    }

    function getContent() {
        return Template::render('301');
    }
}