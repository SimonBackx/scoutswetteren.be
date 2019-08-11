<?php
namespace Pirate\Wheel;

use Pirate\Wheel\Database;

class Model
{
    protected static function getDb()
    {
        return Database::getDb();
    }
}
