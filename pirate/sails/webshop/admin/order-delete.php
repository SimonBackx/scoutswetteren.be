<?php
namespace Pirate\Sails\Webshop\Admin;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class OrderDelete extends Page
{
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
        if (isset($_POST['confirm'])) {
            $this->order->markAsFailed();
            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/order-sheets/{$this->order->order_sheet_id}/orders");
        }

        return Template::render('admin/webshop/order-delete', array(
            'order' => $this->order,
        ));
    }
}
