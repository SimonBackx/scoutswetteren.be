<?php
namespace Pirate\Sails\Webshop\Models;
use Pirate\Wheel\Model;
use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;

abstract class Payment extends Model {
    abstract function save();
    abstract function delete();
    abstract function updateStatus();
    abstract function getName();
}