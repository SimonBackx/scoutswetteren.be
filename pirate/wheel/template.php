<?php
namespace Pirate\Template;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Users\User;

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
    static function render($template, $data = array(), $ext = 'html') {
        if (empty($_SERVER['HTTPS'])) {
            $data['http'] = "http://{$_SERVER['SERVER_NAME']}";
        } else {
            $data['http'] = "https://{$_SERVER['SERVER_NAME']}";
        }

        $data['general'] = array(
            'logged_in' => User::isLoggedIn(),
            'logged_in_leiding' => Leiding::isLoggedIn(),
            'logged_in_ouders' => Ouder::isLoggedIn()
        );

        if (User::isLoggedIn()) {
            $data['logged_in_user']['firstname'] = User::getUser()->firstname;
            $data['logged_in_user']['lastname'] = User::getUser()->lastname;
            $data['logged_in_user']['mail'] = User::getUser()->mail;
            $data['logged_in_user']['phone'] = User::getUser()->phone;
            $data['logged_in_user']['id'] = User::getUser()->id;

            $data['logged_in_user']['name'] = User::getUser()->firstname.' '.User::getUser()->lastname;
        }

        if (Leiding::isLoggedIn()) {
            if (!isset($data['admin']) || !is_array($data['admin'])) {
                $data['admin'] = array();
            }
            $data['admin']['buttons'] = Leiding::getAdminMenu();
            $data['admin']['name'] = Leiding::getUser()->user->firstname.' '.Leiding::getUser()->user->lastname;
        }

        return self::$twig->render($template.'.'.$ext, $data);
    }

}

$loader = new Twig_Loader_Filesystem(__DIR__.'/../templates/');
Template::$twig = new Twig_Environment($loader/*, array(
    'cache' => __DIR__.'/../tmp/twig/',
)*/);