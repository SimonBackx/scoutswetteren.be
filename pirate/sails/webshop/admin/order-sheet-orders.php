<?php
namespace Pirate\Sail\Webshop\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;
use Pirate\Model\Webshop\Order;

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