<?php
namespace Pirate\Sails\Webshop\Admin;

use Pirate\Wheel\Page;
use Pirate\Wheel\Template;

class Order extends Page
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
        if (isset($_POST['paid'])) {
            $this->order->payment->paid();
            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/order-sheets/{$this->order->order_sheet_id}/orders");
        }

        if (isset($_POST['delete'])) {
            $this->order->payment->cancel();
            header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/order-sheets/{$this->order->order_sheet_id}/orders");
        }

        return Template::render('admin/webshop/order', array(
            'order' => $this->order,
        ));
    }
}
