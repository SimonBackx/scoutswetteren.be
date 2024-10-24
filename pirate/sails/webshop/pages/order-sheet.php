<?php
namespace Pirate\Sails\Webshop\Pages;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OrderSheet extends Page
{
    private $order_sheet;

    public function __construct($order_sheet)
    {
        $this->order_sheet = $order_sheet;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        return Template::render('pages/webshop/order-sheet', array(
            'order_sheet' => $this->order_sheet,
        ));
    }
}
