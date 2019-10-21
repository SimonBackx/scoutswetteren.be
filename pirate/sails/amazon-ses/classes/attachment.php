<?php
namespace Pirate\Sails\AmazonSes\Classes;

class Attachment implements \JsonSerializable
{
    public $path;
    public $filename;

    public function __construct($path, $filename)
    {
        $this->path = $path;
        $this->filename = $filename;
    }

    public static function fromJson($obj)
    {
        return new Attachment($obj->path, $obj->filename);
    }

    public function delete()
    {
        try {
            unlink($this->path);
        } catch (\Exception $ex) {
            /// ...
        }
    }

    public function jsonSerialize()
    {
        return [
            'path' => $this->path,
            'filename' => $this->filename,
        ];
    }
}
