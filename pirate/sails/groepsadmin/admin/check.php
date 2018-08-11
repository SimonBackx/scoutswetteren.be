<?php
namespace Pirate\Sail\Groepsadmin\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Groepsadmin\Groepsadmin;
use Pirate\Model\Groepsadmin\GroepsadminLid;
use Pirate\Model\Leden\Lid;
use Pirate\Model\Leden\Ouder;
use Pirate\Model\Leiding\Leiding;

class Check extends Page {
    function __construct() {
    }
    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return 'Bestaat niet meer';
    }
}