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
        global $config;

        $this->client = new \Raven_Client($config['sentry']['url']);
        $error_handler = new \Raven_ErrorHandler($this->client);
        $error_handler->registerExceptionHandler();
        $error_handler->registerErrorHandler();
        $error_handler->registerShutdownFunction();
    }

    function setUser($id, $name, $email) {
        $this->client->user_context(array(
            'user_id' => $id,
            'user_name' => $name,
            'email' => $email,
        ));
    }

    function logFatalError(\Error $error) {
        $this->client->captureMessage('Fatal error: '.$error->getMessage().' in '.$error->getFile().' line '.$error->getLine(), [], ["level" => "fatal"]);
    }

    function setEnvironment($name) {
        $this->client->setEnvironment($name);
    }
}