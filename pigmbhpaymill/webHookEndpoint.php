<?php

include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/paymill/v2/lib/Services/Paymill/Transactions.php');
include_once(dirname(__FILE__) . '/pigmbhpaymill.php');
$request = json_decode(@file_get_contents('php://input'), true);

if (validateNotification($request)) {
    $orderId = getOrderIdFromNotification($request['event']['event_resource']['transaction']['description']);
    $paymill = new PigmbhPaymill();
    $paymill->updateOrderState($orderId);
    echo "OK";
}


// **** FUNCTIONS ****
function validateNotification($notification)
{
    if (isset($notification) && !empty($notification)) {
        // Check eventtype
        if (isset($notification['event']['event_type'])) {
            if ($notification['event']['event_type'] == 'refund.succeeded') {
                $id = null;
                if (isset($notification['event']['event_resource']['transaction']['id'])) {
                    $id = $notification['event']['event_resource']['transaction']['id'];
                }
                $transactionObject = new Services_Paymill_Transactions(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/');
                $result = $transactionObject->getOne($id);
                return $result['id'] === $id;
            }
        }
    }
    return false;
}

function getOrderIdFromNotification($transactionDescription)
{
    $regexPattern = '/OrderID: (\d+)/i';
    $matches = array();
    if (preg_match($regexPattern, $transactionDescription, $matches)) {
        return (int)$matches[1];
    }
    return false;
}