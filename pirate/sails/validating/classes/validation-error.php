<?php
namespace Pirate\Sails\Validating\Classes;

class ValidationError extends ValidationErrorBundle implements \JsonSerializable
{
    public $message;
    public $field;

    public function __construct($message, $field = null)
    {
        $this->message = $message;
        $this->field = $field;
    }

    public function getErrors()
    {
        return [$this];
    }

    public function jsonSerialize()
    {
        if (isset($this->field)) {
            return [
                "message" => $this->message,
                "field" => $this->field,
            ];
        }

        return [
            "message" => $this->message,
        ];
    }
}
