<?php
namespace Pirate\Sail\Contact\Pages;
use Pirate\Page\Page;
use Pirate\Template\Template;

class Contact extends Page {
    function getStatusCode() {
        return 200;
    }

    function getContent() {
       
        return Template::render('contact', array());
    }
}