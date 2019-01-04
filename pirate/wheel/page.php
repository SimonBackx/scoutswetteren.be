<?php
namespace Pirate\Page;
use Pirate\Template\Template;

class Page {
    static function setupAutoload() {
        spl_autoload_register(function ($class) {
            //if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'w'));
            //fwrite(STDOUT, 'Autoload ' . $class."\n");

            $parts = explode('\\', $class);
            if (count($parts) == 5 && $parts[0] == 'Pirate' && $parts[1] == 'Sail' && $parts[3] == 'Pages') {
                Page::loadModel($parts[2], $parts[4]);
            }

            if (count($parts) == 5 && $parts[0] == 'Pirate' && $parts[1] == 'Sail' && $parts[3] == 'Admin') {
                Page::loadAdminModel($parts[2], $parts[4]);
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
        $file = __DIR__.'/../sails/'.strtolower($sail).'/pages/'.camelCaseToDashes($name).'.php';
        //fwrite(STDOUT, 'Autoload ' . $file."\n");
        if (file_exists($file))
            require($file);
        /*else
            fwrite(STDOUT, 'Autoload not found!'."\n");*/

    }    

    static function loadAdminModel($sail, $name) {
        $file = __DIR__.'/../sails/'.strtolower($sail).'/admin/'.camelCaseToDashes($name).'.php';
        //fwrite(STDOUT, 'Autoload ' . $file."\n");
        if (file_exists($file))
            require($file);
        /*else
            fwrite(STDOUT, 'Autoload not found!'."\n");*/

    }  

    function customHeaders() {
        return false;
    }

    function getStatusCode() {
        return 200;
    }

    function getHead() {
        return '';
    }

    function getContent() {
        return 'getContent method not implemented';
    }
    
    function hasOwnLayout() {
        return false;
    }

    final function execute() {
        if (!$this->customHeaders()) {
            http_response_code($this->getStatusCode());
        }
        echo $this->getContent();
    }

    function goodbye() {
        
    }

}

class Page404 extends Page {
    function getStatusCode() {
        return 404;
    }

    function getContent() {
        return Template::render('404');
    }
}

class Page301 extends Page {
    function getStatusCode() {
        return 301;
    }

    function getContent() {
        return Template::render('301');
    }
}

// Temp
class Page302 extends Page {
    function getStatusCode() {
        return 302;
    }

    function getContent() {
        return Template::render('302');
    }
}