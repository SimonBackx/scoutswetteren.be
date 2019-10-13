<?php
namespace Pirate\Sails\Webshop\Pages;

use Pirate\Wheel\Page;

class CancelTransfer extends Page
{
    private $payment;

    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getContent()
    {
        if ($this->payment->status == 'pending') {
            $this->payment->cancel();
        }
        $url = $this->payment->order->getUrl();
        header("Location: $url");
        return "Doorverwijzen naar $url";
    }
}
