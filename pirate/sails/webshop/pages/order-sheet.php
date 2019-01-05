<?php
namespace Pirate\Sail\Webshop\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class OrderSheet extends Page {
    private $order_sheet;

    function __construct($order_sheet) {
        $this->order_sheet = $order_sheet;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('webshop/order-sheet', array(
            'order_sheet' => $this->order_sheet,
        ));
    }
}