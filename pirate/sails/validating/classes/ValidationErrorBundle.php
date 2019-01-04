<?php
namespace Pirate\Classes\Validating;

abstract class ValidationErrorBundle extends \Exception {
    abstract function getErrors();
}
