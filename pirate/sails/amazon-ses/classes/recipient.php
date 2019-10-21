<?php
namespace Pirate\Sails\AmazonSes\Classes;

class Recipient implements \JsonSerializable
{
    public $email;
    public $substitutions = [];

    public $bcc = [];

    public function __construct(Email $email, array $substitutions = [])
    {
        $this->email = $email;
        $this->substitutions = $substitutions;
    }

    public static function fromJson($obj)
    {
        return new Recipient(Email::fromJson($obj->email), (array) $obj->substitutions);
    }

    public function jsonSerialize()
    {
        return [
            'email' => $this->email,
            'substitutions' => $this->substitutions,
        ];
    }

    public function addBcc(Email $email)
    {
        $this->bcc[] = $email;
    }

    public function replace($text, $isHTML = false)
    {
        foreach ($this->substitutions as $key => $value) {
            $text = str_replace("%$key%", $isHTML ? htmlspecialchars($value) : $value, $text);
        }
        return $text;
    }
}
