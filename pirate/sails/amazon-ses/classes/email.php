<?php
namespace Pirate\Sails\AmazonSes\Classes;

class Email
{
    public $email;
    public $name;

    public function __construct($email, $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }
}
