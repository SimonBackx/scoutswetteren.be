<?php
namespace Pirate\Sails\Mailjet\Classes;

class Recipient
{
    public $email;
    public $substitutions = [];

    public $bcc = [];

    public function __construct(Email $email, array $substitutions = [])
    {
        $this->email = $email;
        $this->substitutions = $substitutions;
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
