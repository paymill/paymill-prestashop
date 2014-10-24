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

require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/components/util.php');
require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/paymill/v2/lib/Services/Paymill/Refunds.php');
require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/paymill/v2/lib/Services/Paymill/Transactions.php');

/**
 * OrderActionService
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2014 PayIntelligent GmbH (http://payintelligent.de)
 */
class OrderActionService {

	private $log_identifier;
	private $util;
	private $refund;
	private $transaction;
	private $api_endpoint = 'https://api.paymill.com/v2/';

	public function __construct()
	{
		$private_key = Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY');
		$this->util = new Util();
		$this->refund = new Services_Paymill_Refunds($private_key, $this->api_endpoint);
		$this->transaction = new Services_Paymill_Transactions($private_key, $this->api_endpoint);
	}

	public function refund($order_id)
	{
		$this->log_identifier = time();
		if (!$this->util->isPaymillOrder($order_id))
			return false;

		$data = $this->getTransactionData($order_id);
		try {
			$result = $this->refund->create(array(
				'transactionId' => $data['transaction'],
				'params' => array(
					'amount' => number_format($data['total_paid'], 2) * 100
				)
			));

			$return_value = isset($result['response_code']) && $result['response_code'] === 20000;
			$this->log('Refund resulted in '.(string)$return_value, var_export($result, true));
			$db = Db::getInstance();
			$db->execute('UPDATE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` SET `refund`=1 WHERE `id`='.pSQL($order_id));
		} catch (Exception $exception) {
			$this->log('Refund exception ', var_export($exception->getMessage(), true));
			$return_value = false;
		}
		if ($return_value)
		{
			$new_order_state = Configuration::get('PS_OS_REFUND');
			$order = new Order($order_id);
			$history = new OrderHistory();
			$history->id_order = (int)$order->id;
			$history->changeIdOrderState($new_order_state, (int)$order->id); //order status=3
			$history->add(true);
		}
		return $return_value;
	}

	public function capture($order_id)
	{
		$this->log_identifier = time();
		if (!$this->util->isPaymillOrder($order_id))
			return false;

		$data = $this->getTransactionData($order_id);
		try {
			$result = $this->transaction->create(array(
				'amount' => number_format($data['total_paid'], 2) * 100,
				'currency' => $data['iso_code'],
				'preauthorization' => $data['preauth'],
				'description' => 'OrderId: '.$order_id,
			));

			$return_value = isset($result['id']);
			$this->log('Capture resulted in '.var_export($return_value, true), var_export($result, true));
			$db = Db::getInstance();
			if ($return_value)
				$db->execute('UPDATE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` SET `transaction`="'.
						pSQL($result['id']).'" WHERE `id`='.(int)$order_id);
		} catch (Exception $exception) {
			$this->log('Capture exception ', var_export($exception->getMessage(), true));
			$return_value = false;
		}
		return $return_value;
	}

	private function getTransactionData($order_id)
	{
		$db = Db::getInstance();
		$sql = 'SELECT `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.*, `reference`, `total_paid`, `iso_code` FROM `'._DB_PREFIX_.
				'pigmbh_paymill_transactiondata` LEFT JOIN `'._DB_PREFIX_.'orders` on `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.`id` = `'.
				_DB_PREFIX_.'orders`.`id_order` LEFT JOIN `'._DB_PREFIX_.'currency` on `'._DB_PREFIX_.'orders`.`id_currency` = `'._DB_PREFIX_.
				'currency`.`id_currency` WHERE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.`id`='.(int)$order_id;
		$result = $db->executeS($sql);
		return array_shift($result);
	}

	/**
	 * Log given data if log mode is active
	 * @param string $message
	 * @param mixed $debug_info
	 */
	public function log($message, $debug_info)
	{
		$db = Db::getInstance();
		if (Configuration::get('PIGMBH_PAYMILL_LOGGING') === 'on')
		{
			$db->insert('pigmbh_paymill_logging', array(
				'identifier' => $this->log_identifier,
				'debug' => $db->escape($debug_info),
				'message' => $db->escape($message),
					), false, false, Db::INSERT, true);
		}
	}

}
