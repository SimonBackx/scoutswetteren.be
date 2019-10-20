<?php
namespace Pirate\Sails\Mailjet\Classes;

class Attachment
{
    public $path;
    public $filename;

    public $size;
    public $type;

    public function __construct($path, $filename)
    {
        $this->path = $path;
        $this->filename = $filename;

        $this->size = @filesize($this->path);
        $this->type = mime_content_type($this->path);
    }

    public function toArray()
    {
        $output = @file_get_contents($this->path);

        if ($output === false || $this->size > 10000000) {
            throw new \Exception("File too big or invalid");
        }

        return [
            "ContentType" => $this->type,
            "Filename" => $this->filename,
            "Base64Content" => base64_encode($output),
        ];
    }
}
