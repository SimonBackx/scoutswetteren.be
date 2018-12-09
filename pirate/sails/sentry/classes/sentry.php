<?php
namespace Pirate\Classes\Sentry;

class Sentry {
    static private $instance = null;
    private $client = null;

    static function shared() {
        if (!isset(static::$instance)) {
            static::$instance = new Sentry();
        }
        return static::$instance;
    }

    private function __construct() {
        // required to set the environment before using it!
    }

    function setUser($id, $name, $email) {
        if (!isset($this->client)) {
            return;
        }
        $this->client->user_context(array(
            'user_id' => $id,
            'user_name' => $name,
            'email' => $email,
        ));
    }

    function logFatalError(\Error $error) {
        if (!isset($this->client)) {
            return;
        }
        $this->client->captureMessage('Fatal error: '.$error->getMessage().' in '.$error->getFile().' line '.$error->getLine(), [], ["level" => "fatal"]);
    }

    function setEnvironment($name) {
        global $config;
        if ($name == "development") {
            return;
        }

        if (!isset($this->client)) {
            $this->client = new \Raven_Client($config['sentry']['url']);
            $error_handler = new \Raven_ErrorHandler($this->client);
            $error_handler->registerExceptionHandler();
            $error_handler->registerErrorHandler();
            $error_handler->registerShutdownFunction();
        }
        $this->client->setEnvironment($name);
    }
}