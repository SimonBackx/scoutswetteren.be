<?php
namespace Pirate\Sails\Webshop\Admin;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

class OrderSheetOverview extends Page {
    function __construct($order_sheet) {
        $this->order_sheet = $order_sheet;
    }


    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('admin/webshop/order-sheet-overview', array(
            'sheet' => $this->order_sheet
        ));
    }
}