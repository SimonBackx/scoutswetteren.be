<?php
namespace Pirate\Model;
use Pirate\Database\Database;

class Model {

    static function setupAutoload() {
        spl_autoload_register(function ($class) {
            //if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));
            //fwrite(STDOUT, 'Autoload ' . $class."\n");

            $parts = explode('\\', $class);
            if (count($parts) == 4 && $parts[0] == 'Pirate' && $parts[1] == 'Model') {
                Model::loadModel($parts[2], $parts[3]);
            }
        });
    }

    // Houdt bij welke blocks al in het geheugen geladen zijn
    private static $loadedModels = array();

    /**
     * Laad een model dynamisch in het geheugen
     * @param  [type] $sail naam van de sail die deze block bevat. Zoals in namespace en mapnaam
     * @param  [type] $name klassenaam van de block = bestandsnaam
     * @return  /
     */
    static function loadModel($sail, $name) {
        $file = __DIR__.'/../sails/'.strtolower($sail).'/models/'.strtolower($name).'.php';
        //fwrite(STDOUT, 'Autoload ' . $file."\n");
        if (file_exists($file))
            require($file);
        /*else
            fwrite(STDOUT, 'Autoload not found!'."\n");*/

    }

    protected static function getDb() {
        return Database::getDb();
    }
}