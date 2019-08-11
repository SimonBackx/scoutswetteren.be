<?php
namespace Pirate\Sails\Validating\Classes;

class ValidationError extends ValidationErrorBundle implements \JsonSerializable {
    public $message;
    public $field;

    function __construct($message, $field = null) {
        $this->message = $message;
        $this->field = $field;
    }

    function getErrors() {
        return [$this];
    }

    function jsonSerialize() {
        if (isset($this->field)) {
            return [
                "message" => $this->message,
                "field" => $this->field,
            ];
        }

        return [
            "message" => $this->message
        ];
    }
}
