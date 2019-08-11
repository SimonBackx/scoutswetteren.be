<?php
namespace Pirate\Wheel;

use Pirate\Wheel\Template;

class Page
{
    public function customHeaders()
    {
        return false;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getHead()
    {
        return '';
    }

    public function getContent()
    {
        return 'getContent method not implemented';
    }

    public function hasOwnLayout()
    {
        return false;
    }

    final public function execute()
    {
        if (!$this->customHeaders()) {
            http_response_code($this->getStatusCode());
        }
        echo $this->getContent();
    }

    public function goodbye()
    {

    }

}

class Page404 extends Page
{
    public function getStatusCode()
    {
        return 404;
    }

    public function getContent()
    {
        return Template::render('pages/errors/404');
    }
}

class Page301 extends Page
{
    public function getStatusCode()
    {
        return 301;
    }

    public function getContent()
    {
        return Template::render('pages/errors/301');
    }
}

// Temp
class Page302 extends Page
{
    public function getStatusCode()
    {
        return 302;
    }

    public function getContent()
    {
        return Template::render('pages/errors/302');
    }
}
