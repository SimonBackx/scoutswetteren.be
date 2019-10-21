<?php
namespace Pirate\Sails\AmazonSes\Classes;

class Email implements \JsonSerializable
{
    public $email;
    public $name = null;

    public function __construct($email, $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public static function fromJson($obj)
    {
        if (is_null($obj)) {
            return null;
        }
        return new Email($obj->email, $obj->name);
    }

    public function jsonSerialize()
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
