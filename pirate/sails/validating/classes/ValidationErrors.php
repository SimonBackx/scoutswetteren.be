<?php
namespace Pirate\Classes\Validating;

class ValidationErrors extends ValidationErrorBundle {
    public $errors = [];

    function __construct($errors = []) {
        $this->errors = $errors;
    }

    function extend(...$errors) {
        array_push($this->errors, ...$errors);
    }

    function getErrors() {
        return $this->errors;
    }
}
