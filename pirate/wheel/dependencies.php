<?php
namespace Pirate\Wheel;

class Dependencies
{
    public function check(&$output)
    {
        require __DIR__ . '/dependency.php';

        include __DIR__ . '/../sails/_bindings/dependencies.php';
        if (!isset($dependencies)) {
            $output[] = array('success' => false, 'code' => 0, 'msg' => 'Dependencies not found');
            return false;
        }

        $success = true;
        foreach ($dependencies as $module) {
            $ucfirst_module = ucfirst($module);
            require __DIR__ . "/../sails/$module/dependencies.php";
            $classname = "\\Pirate\\Sails\\$ucfirst_module\\{$ucfirst_module}Dependencies";

            $dependency = new $classname();
            if (!$dependency->check($output)) {
                $success = false;
            }
        }

        return $success;
    }
}
