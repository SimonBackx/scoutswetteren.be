<?php
namespace Pirate\Sails\Webshop\Pages;

use Pirate\Wheel\Page;

class ConfirmTransfer extends Page
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
        $this->payment->confirm();
        $url = $this->payment->order->getUrl();
        header("Location: $url");
        return "Doorverwijzen naar $url";
    }
}
