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
        $input = '{
            "id": "evt_1DuntyAalqE0Xe7WnQsvLL9M",
            "object": "event",
            "api_version": "2018-11-08",
            "created": 1548019942,
            "data": {
              "object": {
                "id": "src_1DuoriAalqE0Xe7WYW0bUSQP",
                "object": "charge",
                "amount": 2300,
                "amount_refunded": 0,
                "application": null,
                "application_fee": null,
                "application_fee_amount": null,
                "balance_transaction": "txn_1DuntyAalqE0Xe7WYUwLEPmf",
                "captured": true,
                "created": 1548019942,
                "currency": "eur",
                "customer": null,
                "description": null,
                "destination": null,
                "dispute": null,
                "failure_code": null,
                "failure_message": null,
                "fraud_details": {
                },
                "invoice": null,
                "livemode": false,
                "metadata": {
                },
                "on_behalf_of": null,
                "order": null,
                "outcome": {
                  "network_status": "approved_by_network",
                  "reason": null,
                  "risk_level": "not_assessed",
                  "seller_message": "Payment complete.",
                  "type": "authorized"
                },
                "paid": true,
                "payment_intent": null,
                "receipt_email": null,
                "receipt_number": null,
                "receipt_url": "https://pay.stripe.com/receipts/acct_1Dbyc6AalqE0Xe7W/py_1DuntyAalqE0Xe7W2FBisj6V/rcpt_ENZ2TeqoyVoB27a1qNqmSN6NSaNvN7B",
                "refunded": false,
                "refunds": {
                  "object": "list",
                  "data": [
          
                  ],
                  "has_more": false,
                  "total_count": 0,
                  "url": "/v1/charges/py_1DuntyAalqE0Xe7W2FBisj6V/refunds"
                },
                "review": null,
                "shipping": null,
                "source": {
                  "id": "src_1Dunt7AalqE0Xe7Wr4LKPrwi",
                  "object": "source",
                  "amount": 2300,
                  "bancontact": {
                    "bank_code": "VAPE",
                    "bank_name": "VAN DE PUT \u0026 CO",
                    "bic": "VAPEBE22",
                    "iban_last4": "7061",
                    "statement_descriptor": null,
                    "preferred_language": null
                  },
                  "client_secret": "src_client_secret_ENZ1ahWoYfTyLUMiqwVupzoE",
                  "created": 1548019942,
                  "currency": "eur",
                  "flow": "redirect",
                  "livemode": false,
                  "metadata": {
                  },
                  "owner": {
                    "address": null,
                    "email": null,
                    "name": "Simon Backx",
                    "phone": "+32 479 42 78 66",
                    "verified_address": null,
                    "verified_email": null,
                    "verified_name": "Jenny Rosen",
                    "verified_phone": null
                  },
                  "redirect": {
                    "failure_reason": null,
                    "return_url": "https://www.scoutswetteren.devhttps://www.scoutswetteren.dev/order/27/6e4f2d4bbad4f94ceb313e65f07c74b834633ca3b19a3cb5c66d6a1dd6f8fdb0",
                    "status": "succeeded",
                    "url": "https://hooks.stripe.com/redirect/authenticate/src_1Dunt7AalqE0Xe7Wr4LKPrwi?client_secret=src_client_secret_ENZ1ahWoYfTyLUMiqwVupzoE"
                  },
                  "statement_descriptor": null,
                  "status": "consumed",
                  "type": "bancontact",
                  "usage": "single_use"
                },
                "source_transfer": null,
                "statement_descriptor": null,
                "status": "succeeded",
                "transfer_data": null,
                "transfer_group": null
              }
            },
            "livemode": false,
            "pending_webhooks": 0,
            "request": {
              "id": "req_ziN2uvM4VtjP6G",
              "idempotency_key": null
            },
            "type": "source.succeeded"
          }';
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