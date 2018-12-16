<?php
namespace Pirate\Sail\Users\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Users\User;

class MagicLink extends Page {

    function customHeaders() {
        return true;
    }

    function getContent() {
        header("Location: ".User::getRedirectURL());
        return 'Bezig met doorverwijzen...';
    }
}