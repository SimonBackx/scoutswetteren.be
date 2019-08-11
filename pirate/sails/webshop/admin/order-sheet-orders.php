<?php
namespace Pirate\Sails\Webshop\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;
use Pirate\Sails\Webshop\Models\Order;

class OrderSheetOrders extends Page {
    function __construct($order_sheet) {
        $this->order_sheet = $order_sheet;
    }


    function getStatusCode() {
        return 200;
    }

    function getContent() {
        $orders = Order::getByOrderSheet($this->order_sheet->id);
        
        return Template::render('admin/webshop/order-sheet-orders', array(
            'sheet' => $this->order_sheet,
            'orders' => $orders,
        ));
    }
}