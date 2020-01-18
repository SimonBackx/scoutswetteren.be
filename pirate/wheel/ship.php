<?php
namespace Pirate\Wheel;

use Pirate\Sails\Environment\Classes\Environment;
use Pirate\Sails\Leiding\Models\Leiding;
use Pirate\Sails\Migrations\Models\Migration;
use Pirate\Sails\Sentry\Classes\Sentry;
use Pirate\Wheel\Database;
use Pirate\Wheel\Template;

class Ship
{
    private $router;

    public function prepare()
    {
        global $FILES_DIRECTORY;
        //$_ENV['DEBUG'] = 1;

        /*ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);*/

        // Catch all errors
        error_reporting(E_ALL);
        set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        date_default_timezone_set('Europe/Brussels');
        setlocale(LC_MONETARY, 'nl_BE');

        // Load procedural files (todo: replace them with classes)
        require __DIR__ . '/config.php';
        require __DIR__ . '/functions.php';

        // Loading all builtin stuff
        require __DIR__ . '/classes.php';
        Classes::setupAutoload();

        // Start Sentry error reporting
        Sentry::shared()->setEnvironment((isset($_ENV["DEBUG"]) && $_ENV["DEBUG"]) ? 'development' : 'production');

        // Loading Sails's services with certain priority level
        Database::init();

        // Load twig (needs environment / autoloading)
        Template::init();

        // Load router
        $this->router = new Router();
    }

    public function sail()
    {
        if ($_SERVER['SERVER_PORT'] != 443) {
            die('Er is een probleem ontstaan waardoor de website geen beveiligde verbinding gebruikt. Neem contact met ons op als dit probleem zich blijft voordoen.');
        }

        $url = strtok($_SERVER["REQUEST_URI"], '?');
        $url = substr($url, 1);

        // Url may never end with a trailing slash to avoid duplicate URI's for the same page
        if (substr($url, -1) == '/') {
            // redirecten NU
            $url = substr($url, 0, strlen($url) - 1);
            $q = $_SERVER['QUERY_STRING'];
            if (strlen($q) > 0) {
                $q = '?' . $q;
            }
            http_response_code(301);
            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/" . $url . $q);
            return;
        }

        try {
            $this->prepare();
            $page = $this->router->route($url);
            // Return the page, set the status code etc.
            //

            $page->execute();

            $page->goodbye();

        } catch (\Exception $e) {
            http_response_code(500);

            if (class_exists('Pirate\Sails\Environment\Classes\Environment')) {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster (' . Environment::getSetting('development_mail.mail') . ') als dit probleem zich blijft voordoen.</p>';
            } else {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster als dit probleem zich blijft voordoen.</p>';
            }

            if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
                echo '<pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }

            if (class_exists('Pirate\Sails\Sentry\Classes\Sentry')) {
                Sentry::shared()->logFatalError($e);
            }
            //Leiding::sendErrorMail("Fatal error", "Fatal error: \n".$e->getFile().' line '.$e->getLine(), $e->getMessage());

            exit;
        } catch (\Error $e) {
            http_response_code(500);

            if (class_exists('Pirate\Sails\Environment\Classes\Environment')) {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster (' . Environment::getSetting('development_mail.mail') . ') als dit probleem zich blijft voordoen.</p>';
            } else {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster als dit probleem zich blijft voordoen.</p>';
            }

            if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
                echo '<pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }

            if (class_exists('Pirate\Sails\Sentry\Classes\Sentry')) {
                Sentry::shared()->logFatalError($e);
            }
            //Leiding::sendErrorMail("Fatal error", "Fatal error: \n".$e->getFile().' line '.$e->getLine(), $e->getMessage());

            exit;
        }

    }

    public function cronjobs()
    {
        echo "Starting Pirate Cronjobs...\n";
        try {
            $this->prepare();
        } catch (\Error $e) {
            echo "Cronjobs failed: \n" . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . "\n";
            if (class_exists('Pirate\Sails\Sentry\Classes\Sentry')) {
                Sentry::shared()->logFatalError($e);
            }
            Leiding::sendErrorMail("Cronjobs failed", "Cronjobs failed \n" . $e->getFile() . ' line ' . $e->getLine(), $e->getMessage());

            exit;
        }

        echo "Cronjobs started\n";
        $cronjobs = new Cronjobs();

        try {
            $cronjobs->run();

        } catch (\Error $e) {
            echo "Cronjobs fatal error \n" . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . "\n";
            if (class_exists('Pirate\Sails\Sentry\Classes\Sentry')) {
                Sentry::shared()->logFatalError($e);
            }
            Leiding::sendErrorMail("Fatal error in cronjobs", "Cronjobs fatal error \n" . $e->getFile() . ' line ' . $e->getLine(), $e->getMessage());
            exit;
        }
    }

    public function install()
    {
        echo "Installing Pirate CMS...\n";
        try {
            $this->prepare();
        } catch (\Error $e) {
            echo "ERR. Installation failed: \n" . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . "\n";
            return false;
        }

        if (!Migration::upgrade()) {
            echo "ERR. Migrations failed!\n";
            return false;
        }

        if (!Cronjobs::install()) {
            echo "ERR. Cronjob installation failed!\n";
            return false;
        }

        echo "Done. Pirate CMS successfully installed.\n";
        return true;
    }
}
