<?php
namespace Pirate\Wheel;

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
