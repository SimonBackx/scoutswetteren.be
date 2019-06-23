<?php
namespace Pirate\Classes\Environment;

class Environment
{
    private $CACHE = null;

    public function loadConfigIfNeeded()
    {
        if (!isset($this->CACHE)) {
            $this->loadConfig();
        }
    }

    public function loadConfig()
    {
        $file = __DIR__ . '/../../../config.php';
        if (!file_exists($file)) {
            throw new \Exception("The config file is not present. Please make sure you have a file config.php located in the pirate folder.");
        }
        $this->CACHE = include $file;
    }

    /// Return a setting of the current environment
    public function getSetting($name, $default = null)
    {
        $this->loadConfigIfNeeded();
        $var = $this->CACHE;
        $path = explode('.', $name);
        foreach ($path as $component) {
            if (is_array($var) && isset($var[$component])) {
                $var = $var[$component];
            } else {
                // not set
                return $default;
            }
        }
        return $var;
    }

}
