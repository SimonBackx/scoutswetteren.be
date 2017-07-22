<?php
namespace Pirate\Sail\Leden\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class MagicLinkPage extends Page {

    function customHeaders() {
        return true;
    }

    function getContent() {
        header("Location: https://".$_SERVER['SERVER_NAME']."/ouders");
        return 'Bezig met doorverwijzen...';
    }
}