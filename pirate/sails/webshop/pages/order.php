<?php
namespace Pirate\Sail\Webshop\Pages;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class Order extends Page {
    private $order;

    function __construct($order) {
        $this->order = $order;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $this->order->payment->updateStatus();
        return Template::render('webshop/order', array(
            'order' => $this->order,
        ));
    }
}