<?php
namespace Pirate\Sail\Webshop\Api;
use Pirate\Page\Page;
use Pirate\Block\Block;
use Pirate\Template\Template;

use Pirate\Model\Webshop\Order;
use Pirate\Model\Webshop\StripePayment;

use Pirate\Classes\Validating\ValidationError;
use Pirate\Classes\Validating\ValidationErrors;
use Pirate\Classes\Validating\ValidationErrorBundle;

class StripeWebhook extends Page {
    function __construct() {

    }

    function getStatusCode() {
        // Retrieve the request's body and parse it as JSON:
        $input = @file_get_contents('php://input');
        $event_json = json_decode($input, true);

        if (!isset($event_json['type'])) {
            return 400;
        }

        $type = $event_json['type'];
        $type_parts = explode('.', $type);

        // Do something with $event_json
        if ($type_parts[0] == 'source') {
            if (!isset($event_json['data']['object']['id'])) {
                return 400;
            }
            $source_id = $event_json['data']['object']['id'];
            $payment = StripePayment::getBySourceId($source_id);
            if (!isset($payment)) {
                return 400;
            }
            $payment->updateStatus();
        }

        return 200;
    }

    function getContent() {
        return '';
    }
}