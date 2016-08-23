<?php
namespace Pirate\Template;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Pirate\Model\Leiding\Leiding;

class Template {
    static public $twig;

    private static function getTemplatePath($template, $sail, $ext) {
         if ( is_null($sail) ) {
            // Use the global defined one
            $url = '/../templates/'.$template.'.'.$ext;

        } else {

            // First check if overwritten
            $url = '/../templates/'.strtolower($sail).'/'.$template.'.'.$ext;

            if ( !file_exists(__DIR__.$url) ) {
                // Not overwritten, so use the default one
                $url = '/../sails/'.strtolower($sail).'/templates/'.$template.'.'.$ext;   
            }
        }
        return __DIR__.$url;
    }

    // Kijkt eerst of de default template overschreven werd (in layout map)
    // in dat geval gebruikt deze functie die template, anders de default zelf in de 
    // sail zelf (layout map in de sail)
    /**
     * [template description]
     * @param  [type] $template [description]
     * @param  [type] $data     [description]
     * @param  (optioneel) [type] $sail     Optioneel
     * @return String    HTML
     */
    static function render($template, $data, $sail = null) {
        $data['http'] = "//{$_SERVER['HTTP_HOST']}";

        $data['general'] = array(
            'logged_in' => Leiding::isLoggedIn()
        );

        if (Leiding::isLoggedIn()) {
            if (isset($data['admin']) && is_array($data['admin'])) {
                $data['admin']['buttons'] = Leiding::getAdminMenu();
            } else {
                $data['admin'] = array(
                    'buttons' => Leiding::getAdminMenu()
                );
            }
        }

        return self::$twig->render($template.'.html', $data);
    }

}

$loader = new Twig_Loader_Filesystem(__DIR__.'/../templates/');
Template::$twig = new Twig_Environment($loader/*, array(
    'cache' => __DIR__.'/../tmp/twig/',
)*/);