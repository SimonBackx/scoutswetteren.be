<?php
namespace Pirate\Wheel;

class Classes
{

    public static function setupAutoload()
    {
        spl_autoload_register(function ($class) {

            $parts = explode('\\', $class);
            if (count($parts) >= 2 && $parts[0] == 'Pirate' && ($parts[1] === 'Wheel' || $parts[1] === 'Sails')) {
                Classes::loadClass($parts);
            }
        });
    }

    // Houdt bij welke blocks al in het geheugen geladen zijn
    private static $loadedModels = array();

    /**
     * Laad een class dynamisch in het geheugen
     * @param  [type] $sail naam van de sail die deze block bevat. Zoals in namespace en mapnaam
     * @param  [type] $name klassenaam van de block = bestandsnaam
     * @return  /
     */
    public static function loadClass($parts)
    {
        // Remove pirate part
        $pirate = array_shift($parts);
        if ($pirate !== 'Pirate') {
            return;
        }
        $file = __DIR__ . '/..';
        foreach ($parts as $part) {
            $file .= '/' . camelCaseToDashes($part);
        }
        $file .= '.php';

        if (file_exists($file)) {
            require $file;
        } else {
            echo $file;
            // try to load without strtolower (fix)
            /*$file = __DIR__ . '/../sails/' . strtolower($sail) . '/classes/' . $name . '.php';
        if (file_exists($file)) {
        require $file;
        }*/
        }

    }
}
