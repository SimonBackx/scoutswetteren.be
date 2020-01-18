<?php
namespace Pirate\Sails\Webshop\Api;

use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;
use Pirate\Sails\Webshop\Models\Order;
use Pirate\Wheel\Page;

class CreateOrder extends Page
{
    private $order_sheet;
    private $data;
    private $response;

    public function __construct($order_sheet, $data)
    {
        $this->order_sheet = $order_sheet;
        $this->data = $data;
    }

    public function getStatusCode()
    {
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
                'redirect' => isset($order->payment) ? $order->payment->getNextUrl() : $order->getUrl(),
            ];

        } catch (ValidationErrorBundle $bundle) {
            $this->response = $bundle->getErrors();
            return 400;
        }

        return 200;
    }

    public function getContent()
    {
        header('Content-Type: application/json');
        return json_encode($this->response);
    }
}
