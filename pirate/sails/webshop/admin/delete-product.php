<?php
namespace Pirate\Sail\Webshop\Admin;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

class DeleteProduct extends Page {
    private $product = null;
    private $order_sheet = null;

    function __construct($product = null, $order_sheet = null) {
        $this->product = $product;
        $this->order_sheet = $order_sheet;
    }

    function getStatusCode() {
        return 200;
    }

    function getContent() {
        // Geen geldig id = nieuw event toevoegen
        $success = false;
        $fail = false;
   
        if (isset($_POST['confirm-delete'])) {
            // Echt verwijderen en doorverwijzen
            $success = $this->product->delete();

            if ($success) {
                if (isset($this->order_sheet)) {
                    $id = $this->order_sheet->id;
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/order-sheets/$id");
                } else {
                    header("Location: https://".$_SERVER['SERVER_NAME']."/admin/products");
                }
            } else {
                $fail = true;
            }
            
        }

        return Template::render('admin/webshop/delete-product', array(
            'product' => $this->product,
            'order_sheet' => $this->order_sheet,
            'success' => $success,
            'fail' => $fail,
        ));
    }
}