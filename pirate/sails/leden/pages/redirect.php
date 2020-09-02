<?php
namespace Pirate\Sails\Leden\Pages;
use Pirate\Wheel\Page;
use Pirate\Sails\Environment\Classes\Environment;

class Redirect extends Page {

    function getStatusCode() {
        return 302;
    }

    function getContent() {
        $url = Environment::getSetting('scouts.override_url');
        header("Location: " . $url);
        return "Doorverwijzen naar " . $url;
    }
}