<?php
namespace Pirate\Sails\Webshop\Api;
use Pirate\Wheel\Page;
use Pirate\Wheel\Block;
use Pirate\Wheel\Template;

use Pirate\Sails\Webshop\Models\Order;
use Pirate\Sails\Webshop\Models\StripePayment;

use Pirate\Sails\Validating\Classes\ValidationError;
use Pirate\Sails\Validating\Classes\ValidationErrors;
use Pirate\Sails\Validating\Classes\ValidationErrorBundle;

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