<?php
namespace Pirate\Sail\Webshop;
use Pirate\Page\Page;
use Pirate\Route\AdminRoute;
use Pirate\Model\Webshop\OrderSheet;
use Pirate\Model\Webshop\Product;

class WebshopAdminRouter extends AdminRoute {

    function doMatch($url, $parts) {
        if ($result = $this->match($parts, '/order-sheets/@id/products', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Admin\OrderSheetProducts($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/products/edit')) {
            $this->setPage(new Admin\EditProduct());
            return true;
        }

        if ($result = $this->match($parts, '/products/edit/@id', ['id' => 'string'])) {
            $product = Product::getById($result->params->id);

            if (!isset($product)) {
                return false;
            }
            $this->setPage(new Admin\EditProduct($product));
            return true;
        }
    }

}