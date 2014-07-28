<?php
/**
 * 2012-2014 PAYMILL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    PAYMILL <support@paymill.com>
 *  @copyright 2012-2014 PAYMILL
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/paymill/v2/lib/Services/Paymill/Transactions.php');
include_once(dirname(__FILE__).'/pigmbhpaymill.php');
$request = Tools::jsonDecode(Tools::file_get_contents('php://input'), true);

if (validateNotification($request))
{
	$order_id = getOrderIdFromNotification($request['event_resource']['transaction']['description']);

	$dbResult = Db::getInstance()->executeS('SELECT `id_order_state` FROM `'._DB_PREFIX_
			.'order_state_lang` WHERE `template` = "refund" GROUP BY `template`;');
	$newOrderState = (int)$dbResult[0]['id_order_state'];
	$objOrder = new Order($order_id);
	$history = new OrderHistory();
	$history->id_order = (int)$objOrder->id;
	$history->changeIdOrderState($newOrderState, (int)($objOrder->id)); //order status=3
	$history->add(true);

	echo 'OK';
}

function validateNotification($notification)
{
	$result = false;
	if (isNotificationFormatValid($notification) && $notification['event_type'] === 'refund.succeeded')
	{
		$transaction_object = new Services_Paymill_Transactions(
			Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/'
		);
		$id = $notification['event_resource']['transaction']['id'];
		$transaction_result = $transaction_object->getOne($id);
		$result = isset($transaction_result['id']) && $transaction_result['id'] === $id;

	}
	return $result;
}

function isNotificationFormatValid($notification)
{
	return isset($notification) && isset($notification['event_type']) && isset($notification['event_resource']['transaction']['id']);
}

function getOrderIdFromNotification($transaction_description)
{
	$regex_pattern = '/OrderID: (\d+)/i';
	$matches = array();
	if (preg_match($regex_pattern, $transaction_description, $matches))
		return (int)$matches[1];

	return false;
}
