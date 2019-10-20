<?php
namespace Pirate\Sails\Mailjet\Classes;

class Email
{
    public $email;
    public $name;

    public function __construct($email, $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function toArray()
    {
        if (empty($this->name)) {
            return [
                "Email" => $this->email,
            ];
        }
        return [
            "Email" => $this->email,
            "Name" => $this->name,
        ];
    }
}
