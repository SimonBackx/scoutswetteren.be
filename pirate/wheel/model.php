<?php
namespace Pirate\Model;
use Pirate\Database\Database;

class Model {
    // Houdt bij welke blocks al in het geheugen geladen zijn
    private static $loadedModels = array();

    /**
     * Laad een block dynamisch in het geheugen als dit nog niet is gebeurd en geeft deze terug.
     * @param  [type] $sail naam van de sail die deze block bevat. Zoals in namespace en mapnaam
     * @param  [type] $name klassenaam van de block = bestandsnaam
     * @return Block       [description]
     */
    static function loadModel($sail, $name) {
        // TODO: indien niet gevonden: lege block meegeven of een error block.
        
        if (!isset($loadedModels[$sail][$name])) {
            // TODO: Extra interne beveiliging hier toevoegen: . / \ en sepciale tekens blokkeren
            require(__DIR__.'/../sails/'.strtolower($sail).'/models/'.strtolower($name).'.php');
        }
    }

    protected static function getDb() {
        return Database::getDb();
    }
}