<?php
namespace Pirate\Wheel;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leden\Models\Ouder;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Users\Models\User;

class Template
{
    public static $twig;

    private static function getTemplatePath($template, $sail, $ext)
    {
        if (is_null($sail)) {
            // Use the global defined one
            $url = '/../templates/' . $template . '.' . $ext;

        } else {

            // First check if overwritten
            $url = '/../templates/' . strtolower($sail) . '/' . $template . '.' . $ext;

            if (!file_exists(__DIR__ . $url)) {
                // Not overwritten, so use the default one
                $url = '/../sails/' . strtolower($sail) . '/templates/' . $template . '.' . $ext;
            }
        }
        return __DIR__ . $url;
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
    public static function render($template, $data = array(), $ext = 'html')
    {
        if (empty($_SERVER['HTTPS'])) {
            $data['http'] = "http://{$_SERVER['SERVER_NAME']}";
        } else {
            $data['http'] = "https://{$_SERVER['SERVER_NAME']}";
        }

        $data['environment'] = [
            'domain' => Environment::getSetting('domain'),
            'name' => Environment::getSetting('name'),
            'theme' => Environment::getSetting('theme'),
            'development_mail' => Environment::getSetting('development_mail'),
            'drive' => Environment::getSetting('drive'),
            'stamhoofd' => Environment::getSetting('stamhoofd'),
        ];

        $url = isset($_SERVER["REQUEST_URI"]) ? strtok($_SERVER["REQUEST_URI"], '?') : '/';

        $data['general'] = array(
            'url' => $url,
            'logged_in' => User::isLoggedIn(),
            'logged_in_leiding' => Leiding::isLoggedIn(),
            'logged_in_leiding_redacteur' => Leiding::hasPermission('redacteur'),
            'logged_in_ouders' => Ouder::isLoggedIn(),
        );

        if (User::isLoggedIn()) {
            $data['logged_in_user']['firstname'] = User::getUser()->firstname;
            $data['logged_in_user']['lastname'] = User::getUser()->lastname;
            $data['logged_in_user']['mail'] = User::getUser()->mail;
            $data['logged_in_user']['phone'] = User::getUser()->phone;
            $data['logged_in_user']['id'] = User::getUser()->id;

            $data['logged_in_user']['name'] = User::getUser()->firstname . ' ' . User::getUser()->lastname;
        }

        if (Leiding::isLoggedIn()) {
            if (!isset($data['admin']) || !is_array($data['admin'])) {
                $data['admin'] = array();
            }
            $data['admin']['buttons'] = Leiding::getAdminMenu();
            $data['admin']['name'] = Leiding::getUser()->user->firstname . ' ' . Leiding::getUser()->user->lastname;
        }

        return self::$twig->render($template . '.' . $ext, $data);
    }

    public static function init()
    {
        $loader = new \Twig\Loader\FilesystemLoader([__DIR__ . '/../themes/' . Environment::getSetting('theme') . '/templates/', __DIR__ . '/../themes/shared/templates/']);
        $config = [];

        if (!isset($_ENV["DEBUG"]) || $_ENV["DEBUG"] != 1) {
            //$config['cache'] = __DIR__ . '/../tmp/twig/';
            // disable twig cache.
        }

        Template::$twig = new \Twig\Environment($loader, $config);
    }

}
