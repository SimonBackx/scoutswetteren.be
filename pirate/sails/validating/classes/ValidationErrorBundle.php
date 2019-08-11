<?php
namespace Pirate\Sails\Validating\Classes;

abstract class ValidationErrorBundle extends \Exception {
    abstract function getErrors();
}
