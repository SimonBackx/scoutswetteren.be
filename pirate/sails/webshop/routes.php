<?php
namespace Pirate\Sails\Webshop;

use Pirate\Sails\Webshop\Models\Order;
use Pirate\Sails\Webshop\Models\OrderSheet;
use Pirate\Sails\Webshop\Models\TransferPayment;
use Pirate\Wheel\Route;

class WebshopRouter extends Route
{
    public function doMatch($url, $parts)
    {
        /*if ($match = $this->match($parts, '/gebruikers/wachtwoord-kiezen/@key', ['key' => 'string'])) {
        if (User::temporaryLoginWithPasswordKey($match->params->key)) {
        $this->setPage(new Pages\SetPassword());
        return true;
        }
        return false;
        }*/

        if ($result = $this->match($parts, '/order/@id/@secret', ['id' => 'string', 'secret' => 'string'])) {
            $order = Order::getById($result->params->id);
            if (!isset($order) || $order->secret != $result->params->secret) {
                return false;
            }
            $this->setPage(new Pages\Order($order));
            return true;
        }

        if ($result = $this->match($parts, '/inschrijvingen/@id/@slug', ['id' => 'string', 'slug' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Pages\OrderSheet($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/bestellingen/@id/@slug', ['id' => 'string', 'slug' => 'string'])) {
            $order_sheet = OrderSheet::getById($result->params->id);
            if (!isset($order_sheet)) {
                return false;
            }
            $this->setPage(new Pages\OrderSheet($order_sheet));
            return true;
        }

        if ($result = $this->match($parts, '/transfer-payment/@id/confirm', ['id' => 'string'])) {
            $payment = TransferPayment::getById($result->params->id);
            if (!isset($payment)) {
                return false;
            }
            $this->setPage(new Pages\ConfirmTransfer($payment));
            return true;
        }

        return false;
    }
}
