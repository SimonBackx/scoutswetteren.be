<?php
namespace Pirate\Sails\Environment\Classes;

class Environment
{
    private static $CACHE = null;

    public static function loadConfigIfNeeded()
    {
        if (!isset(self::$CACHE)) {
            self::loadConfig();
        }
    }

    public static function loadConfig()
    {
        $file = __DIR__ . '/../../../config.php';
        if (!file_exists($file)) {
            throw new \Exception("The config file is not present. Please make sure you have a file config.php located in the pirate folder.");
        }
        self::$CACHE = include $file;
    }

    /// Return a setting of the current environment
    public static function getSetting($name, $default = null)
    {
        self::loadConfigIfNeeded();
        $var = self::$CACHE;
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
