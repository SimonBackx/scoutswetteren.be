<?php
namespace Pirate\Classes\Sentry;

use Pirate\Classes\Environment\Environment;

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
        if (!isset($this->client)) {
            return;
        }
        $this->client->user_context(array(
            'user_id' => $id,
            'user_name' => $name,
            'email' => $email,
        ));
    }

    public function logFatalError($error)
    {
        if (!isset($this->client)) {
            return;
        }
        $this->client->captureMessage('Fatal error: ' . $error->getMessage() . ' in ' . $error->getFile() . ' line ' . $error->getLine(), [], ["level" => "fatal"]);
    }

    public function setEnvironment($name)
    {
        if ($name == "development") {
            return;
        }

        if (!isset($this->client)) {
            $this->client = new \Raven_Client(Environment::getSetting('sentry.url'));
            $error_handler = new \Raven_ErrorHandler($this->client);
            $error_handler->registerExceptionHandler();
            $error_handler->registerErrorHandler();
            $error_handler->registerShutdownFunction();
        }
        $this->client->setEnvironment($name);
    }
}
