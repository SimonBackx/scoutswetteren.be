<?php
namespace Pirate;

use Pirate\Classes\Environment\Environment;
use Pirate\Classes\Sentry\Sentry;
use Pirate\Database\Database;
use Pirate\Model\Leiding\Leiding;
use Pirate\Model\Migrations\Migration;
use Pirate\Model\Model;
use Pirate\Template\Template;

class Ship
{
    private $router;

    public function prepare()
    {
        global $FILES_DIRECTORY;

        /*ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);*/

        date_default_timezone_set('Europe/Brussels');
        setlocale(LC_MONETARY, 'nl_BE.UTF-8', 'nl_BE');

        require __DIR__ . '/config.php';

        // Loading all builtin stuff
        require __DIR__ . '/template.php';
        require __DIR__ . '/database.php';
        require __DIR__ . '/classes.php';
        Classes::setupAutoload();

        // Start Sentry error reporting
        Sentry::shared()->setEnvironment((isset($_ENV["DEBUG"]) && $_ENV["DEBUG"]) ? 'development' : 'production');

        require __DIR__ . '/functions.php';
        require __DIR__ . '/model.php';
        require __DIR__ . '/mail.php';
        require __DIR__ . '/dependencies.php';
        require __DIR__ . '/cronjob.php';
        require __DIR__ . '/curl.php';

        // Loading Sails's services with certain priority level
        Database::init();

        // Load twig (needs environment / autoloading)
        Template::init();

        // Load router
        require __DIR__ . '/router.php';
        $this->router = new Route\Router();

        // autoloader voor models laden:
        Model::setupAutoload();
    }

    public function sail()
    {
        if ($_SERVER['SERVER_PORT'] != 443) {
            die('Er is een probleem ontstaan waardoor de website geen beveiligde verbinding gebruikt. Neem conact met ons op als dit probleem zich blijft voordoen.');
        }

        $url = strtok($_SERVER["REQUEST_URI"], '?');
        $url = substr($url, 1);
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

            if (class_exists('Environment')) {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster (' . Environment::getSetting('development_mail.mail') . ') als dit probleem zich blijft voordoen.</p><pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
            } else {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster als dit probleem zich blijft voordoen.</p><pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
            }

            if (class_exists('Sentry')) {
                Sentry::shared()->logFatalError($e);
            }
            //Leiding::sendErrorMail("Fatal error", "Fatal error: \n".$e->getFile().' line '.$e->getLine(), $e->getMessage());

            exit;
        } catch (\Error $e) {
            http_response_code(500);

            if (class_exists('Environment')) {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster (' . Environment::getSetting('development_mail.mail') . ') als dit probleem zich blijft voordoen.</p><pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
            } else {
                echo '<p>Oeps! Er ging iets mis op de website. Neem contact op met onze webmaster als dit probleem zich blijft voordoen.</p><pre>' . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . '</pre>';
            }
            if (class_exists('Sentry')) {
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

            Leiding::sendErrorMail("Cronjobs failed", "Cronjobs failed \n" . $e->getFile() . ' line ' . $e->getLine(), $e->getMessage());

            exit;
        }

        echo "Cronjobs started\n";
        $cronjobs = new Cronjob\Cronjobs();

        try {
            $cronjobs->run();

        } catch (\Error $e) {
            echo "Cronjobs fatal error \n" . $e->getFile() . ' line ' . $e->getLine() . ' ' . $e->getMessage() . "\n";

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

        if (!Cronjob\Cronjobs::install()) {
            echo "ERR. Cronjob installation failed!\n";
            return false;
        }

        echo "Done. Pirate CMS successfully installed.\n";
        return true;
    }
}
