<?php
namespace Pirate\Sails\AmazonSes\Classes;

class Attachment
{
    public $path;
    public $filename;

    public function __construct($path, $filename)
    {
        $this->path = $path;
        $this->filename = $filename;
    }
}
