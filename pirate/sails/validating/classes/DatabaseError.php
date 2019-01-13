<?php
namespace Pirate\Classes\Validating;

class DatabaseError extends ValidationErrorBundle implements \JsonSerializable {
    public $message;

    function __construct($message) {
        $this->message = $message;
    }

    function getErrors() {
        return [$this];
    }

    function jsonSerialize() {
        return [
            "message" => $this->message
        ];
    }
}
