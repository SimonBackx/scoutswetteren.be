<?php
namespace Pirate\Block;

class Block {


    public $scripts = array();
    public $styles = array();

    function getHead() {
        return '';
    }
    
    function getContent() {
        return 'Empty block';
    }

    // Static gedeelte

    // Houdt bij welke blocks al in het geheugen geladen zijn
    private static $loadedBlocks = array();

    /**
     * Laad een block dynamisch in het geheugen als dit nog niet is gebeurd en geeft deze terug.
     * @param  [type] $sail naam van de sail die deze block bevat. Zoals in namespace en mapnaam
     * @param  [type] $name klassenaam van de block = bestandsnaam
     * @return Block       [description]
     */
    static function getBlock($sail, $name) {
        // TODO: indien niet gevonden: lege block meegeven of een error block.
        
        if (!isset($loadedBlocks[$sail][$name])) {
            // TODO: Extra interne beveiliging hier toevoegen: . / \ en sepciale tekens blokkeren
            require(__DIR__.'/../sails/'.strtolower($sail).'/blocks/'.strtolower($name).'.php');
        }
        if (!isset($loadedBlocks[$sail])) {
            $loadedBlocks[$sail] = array();
        }
        $loadedBlocks[$sail][$name] = true;

        $classname = "Pirate\\Sail\\$sail\\Blocks\\$name";
        return new $classname();
    }
}