<?php
namespace Pirate\Classes\Validating;

class ValidationError extends ValidationErrorBundle {
    public $message;
    public $field;

    function __construct($message, $field = null) {
        $this->message = $message;
        $this->field = $field;
    }

    function getErrors() {
        return [$this];
    }
}
