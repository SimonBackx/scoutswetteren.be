<?php
namespace Pirate\Sails\Webshop;

use Pirate\Sails\Webshop\Models\Order;
use Pirate\Sails\Webshop\Models\OrderSheet;
use Pirate\Sails\Webshop\Models\Product;
use Pirate\Wheel\AdminRoute;

class WebshopAdminRouter extends AdminRoute
{

    public function doMatch($url, $parts)
    {
        if ($result = $this->match($parts, '/order-sheets/@id', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Admin\OrderSheetOverview($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/order-sheets/@id/orders', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Admin\OrderSheetOrders($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/order-sheets/@id/excel', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Admin\OrderSheetExcel($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/order-sheets/@id/products/new', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Admin\EditProduct(null, $order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/products/new')) {
            $this->setPage(new Admin\EditProduct());
            return true;
        }

        if ($result = $this->match($parts, '/order-sheets/@id/products/@product_id', ['id' => 'string', 'product_id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }

            $product = Product::getById($result->params->product_id);

            if (!isset($product)) {
                return false;
            }
            $this->setPage(new Admin\EditProduct($product, $order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/order-sheets/@id/products/delete/@product_id', ['id' => 'string', 'product_id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }

            $product = Product::getById($result->params->product_id);

            if (!isset($product)) {
                return false;
            }
            $this->setPage(new Admin\DeleteProduct($product, $order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/products/@id', ['id' => 'string'])) {
            $product = Product::getById($result->params->id);

            if (!isset($product)) {
                return false;
            }
            $this->setPage(new Admin\EditProduct($product));
            return true;
        }

        if ($result = $this->match($parts, '/orders/@id', ['id' => 'string'])) {
            $order = Order::getById($result->params->id);
            if (!isset($order)) {
                return false;
            }
            $this->setPage(new Admin\Order($order));
            return true;
        }

        if ($result = $this->match($parts, '/orders/@id/delete', ['id' => 'string'])) {
            $order = Order::getById($result->params->id);
            if (!isset($order)) {
                return false;
            }
            $this->setPage(new Admin\OrderDelete($order));
            return true;
        }
    }

}
