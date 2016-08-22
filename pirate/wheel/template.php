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

        return self::$twig->render($template.'.html', $data);


        /*extract($data);

        // Hulp variabelen
        $http = "//{$_SERVER['HTTP_HOST']}";

        ob_start();
        include(self::getTemplatePath($template, $sail, 'php'));
        $content = ob_get_contents();
        ob_end_clean();
        return $content;*/
    }

    static function renderSimple($template, $replacement, $sail = null) {
        $template = file_get_contents(self::getTemplatePath($template, $sail, 'html'));

        $patterns= array('/{{ ?http ?}}/');
        $http = "//{$_SERVER['HTTP_HOST']}";
        $replacements = array($http);

        foreach ($replacement as $key => $value) {

            if (is_array($value)) {

            }
            // unescaped
            $patterns[] = '/{{{ ?'.$key.' ?}}}/';
            $replacements[] = $value;

            // escaped
            $patterns[] = '/{{ ?'.$key.' ?}}/';
            $replacements[] = htmlspecialchars($value);
        }
        return preg_replace($patterns, $replacements, $template);
    }
}

$loader = new Twig_Loader_Filesystem(__DIR__.'/../templates/');
Template::$twig = new Twig_Environment($loader/*, array(
    'cache' => __DIR__.'/../tmp/twig/',
)*/);