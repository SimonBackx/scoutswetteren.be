<?php
namespace Pirate\Sails\Users\Pages;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Users\Models\User;

class MagicLink extends Page {

    function customHeaders() {
        return true;
    }

    function getContent() {
        header("Location: ".User::getRedirectURL());
        return 'Bezig met doorverwijzen...';
    }
}