<?php
namespace Pirate\Model\Webshop;
use Pirate\Model\Model;
use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

abstract class Payment extends Model {
    abstract function save();
    abstract function delete();
    abstract function updateStatus();
    abstract function getName();
}