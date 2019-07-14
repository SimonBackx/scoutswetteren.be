<?php
namespace Pirate\Sail\Webshop\Pages;

use Pirate\Page\Page;
use Pirate\Template\Template;

class Order extends Page
{
    private $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        $this->order->payment->updateStatus();
        return Template::render('pages/webshop/order', array(
            'order' => $this->order,
        ));
    }
}
