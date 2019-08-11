<?php
namespace Pirate\Wheel;

class Dependency
{
    public function check(&$output)
    {
        $output[] = array('success' => false, 'code' => 0, 'msg' => 'Using default dependency. Failed.');
        return false;
    }

    public function fix(&$errors)
    {
        // try to fix the dependencies
        // true on success
        return false;
    }
}
