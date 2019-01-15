<?php
namespace Pirate\Sail\Webshop\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

use Pirate\Model\Webshop\Order;

use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

class CreateOrder extends Page {
    private $order_sheet;
    private $data;
    private $response;

    function __construct($order_sheet, $data) {
        $this->order_sheet = $order_sheet;
        $this->data = $data;
    }

    function getStatusCode() {
        // Block ophalen van maandplanning sail
        $order = new Order();
        $this->response = [];
        try {
            $order->setProperties($this->data);
            $order->order_sheet = $this->order_sheet;

            if (!$order->save()) {
                throw new ValidationError("Er ging iets mis bij het opslaan");
            }

            /// Set payment method (will throw on error)
            $order->setPayment($this->data, $this->order_sheet->bank_account);



            $this->response = [
                'redirect' => $order->payment->getNextUrl()
            ];

        } catch (ValidationErrorBundle $bundle) {
            $this->response = $bundle->getErrors();
            return 400;
        }

        return 200;
    }

    function getContent() {
        header('Content-Type: application/json');
        return json_encode($this->response);
    }
}