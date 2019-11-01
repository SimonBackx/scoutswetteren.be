<?php
namespace Pirate\Sails\Sentry\Classes;

use Pirate\Sails\Environment\Classes\Environment;
use Sentry as S;

class Sentry
{
    private static $instance = null;
    private $client = null;

    public static function shared()
    {
        if (!isset(static::$instance)) {
            static::$instance = new Sentry();
        }
        return static::$instance;
    }

    private function __construct()
    {
        // required to set the environment before using it!
    }

    public function setUser($id, $name, $email)
    {
        S\configureScope(function (S\State\Scope $scope) use ($id, $email, $name) {
            $scope->setUser(['id' => $id, 'email' => $email, 'username' => $name]);
        });
    }

    public function logFatalError($error)
    {
        S\captureException($error);
    }

    public function setEnvironment($name)
    {
        if ($name == "development") {
            return;
        }

        S\init(['dsn' => Environment::getSetting('sentry.url')]);
        S\configureScope(function (S\State\Scope $scope) use ($name) {
            $scope->setExtra(['environment' => $name]);
        });

    }
}
