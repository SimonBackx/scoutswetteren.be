<?php
namespace Pirate\Sail\Webshop\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class OrderSheetProducts extends Page {
    function __construct($order_sheet) {
        $this->order_sheet = $order_sheet;
    }


    function getStatusCode() {
        return 200;
    }

    function getContent() {
        return Template::render('webshop/admin/order-sheet-products', array(
            'sheet' => $this->order_sheet
        ));
    }
}