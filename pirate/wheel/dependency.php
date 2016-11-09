<?php
namespace Pirate\Dependency;

class Dependency {
    function check(&$output) {
        $output[] = array('success' => false, 'code' => 0, 'msg' => 'Using default dependency. Failed.');
        return false;
    }

    function fix(&$errors) {
        // try to fix the dependencies
        // true on success
        return false;
    }
}