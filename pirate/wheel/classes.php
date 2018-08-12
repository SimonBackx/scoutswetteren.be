<?php
namespace Pirate;

class Classes  {

    static function setupAutoload() {
        spl_autoload_register(function ($class) {

            $parts = explode('\\', $class);
            if (count($parts) == 4 && $parts[0] == 'Pirate' && $parts[1] == 'Classes') {
                Classes::loadClass($parts[2], $parts[3]);
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
    static function loadClass($sail, $name) {
        $file = __DIR__.'/../sails/'.strtolower($sail).'/classes/'.strtolower($name).'.php';
        
        if (file_exists($file)) {
            require($file);
        }

    }
}