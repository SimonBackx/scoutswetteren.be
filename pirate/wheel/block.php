<?php
namespace Pirate\Wheel;

class Block
{

    public $scripts = array();
    public $styles = array();

    public function getHead()
    {
        return '';
    }

    public function getContent()
    {
        return 'Empty block';
    }

    // Static gedeelte

    /**
     * Laad een block dynamisch in het geheugen als dit nog niet is gebeurd en geeft deze terug.
     * @param  [type] $sail naam van de sail die deze block bevat. Zoals in namespace en mapnaam
     * @param  [type] $name klassenaam van de block = bestandsnaam
     * @return Block       [description]
     */
    public static function getBlock($sail, $name)
    {
        $classname = "Pirate\\Sails\\$sail\\Blocks\\$name";
        return new $classname();
    }
}
