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
	$order_id = getOrderIdFromNotification($request['event']['event_resource']['transaction']['description']);
	$paymill = new PigmbhPaymill();
	$paymill->updateOrderState($order_id);
	echo 'OK';
}

function validateNotification($notification)
{
	if (isset($notification) && !empty($notification))
	{
		// Check eventtype
		if (isset($notification['event']['event_type']))
		{
			if ($notification['event']['event_type'] == 'refund.succeeded')
			{
				$id = null;
				if (isset($notification['event']['event_resource']['transaction']['id']))
					$id = $notification['event']['event_resource']['transaction']['id'];

				$transaction_object = new Services_Paymill_Transactions(
					Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/'
				);

				$result = $transaction_object->getOne($id);
				return $result['id'] === $id;
			}
		}
	}

	return false;
}

function getOrderIdFromNotification($transaction_description)
{
	$regex_pattern = '/OrderID: (\d+)/i';
	$matches = array();
	if (preg_match($regex_pattern, $transaction_description, $matches))
		return (int)$matches[1];

	return false;
}
