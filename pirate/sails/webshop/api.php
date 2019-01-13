<?php
namespace Pirate\Sail\Webshop;
use Pirate\Page\Page;
use Pirate\Route\Route;
use Pirate\Model\Webshop\OrderSheet;

class WebshopApiRouter extends Route {
    function doMatch($url, $parts) {
        if ($result = $this->match($parts, '/place-order/@id', ['id' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            try {
                $inputJSON = file_get_contents('php://input');
                $input = json_decode($inputJSON, true); //convert JSON into array
                $this->setPage(new Api\CreateOrder($order_sheet, $input));
                return true;

            } catch (\Throwable $ex) {
                // Invalid input
            }
            
            return false;
        }

        return false;
    }
}