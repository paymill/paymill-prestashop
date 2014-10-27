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

require_once dirname(__FILE__).'/../../paymill/v2/lib/Services/Paymill/PaymentProcessor.php';
require_once dirname(__FILE__).'/../../paymill/v2/lib/Services/Paymill/LoggingInterface.php';
require_once dirname(__FILE__).'/../../paymill/v2/lib/Services/Paymill/Transactions.php';

/**
 * validation
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
class PigmbhpaymillValidationModuleFrontController extends ModuleFrontController implements Services_Paymill_LoggingInterface {

	/**
	 * @var Services_Paymill_PaymentProcessor
	 */
	private $payment_processor;

	/**
	 * @var string
	 */
	private $token;

	/**
	 * @var Db
	 */
	private $db;

	/**
	 * @var string
	 */
	private $payment;

	/**
	 * @var string
	 */
	private $iso_currency;

	/**
	 * @var int
	 */
	private $log_id;

	/**
	 * Initialize needed class variables and session
	 */
	private function paymillInit()
	{
		$this->log_id = time();
		$this->db = Db::getInstance();
		$this->token = Tools::getValue('paymillToken');
		$this->payment = Tools::getValue('payment');
		$valid_payments = array();
		if (Configuration::get('PIGMBH_PAYMILL_DEBIT'))
			$valid_payments[] = 'debit';
		if (Configuration::get('PIGMBH_PAYMILL_CREDITCARD'))
			$valid_payments[] = 'creditcard';

		if (empty($this->token))
		{
			$this->log('No paymill token was provided. Redirect to payments page.', null);
			Tools::redirect($this->context->link->getPageLink('order', true, null, array('step' => '1', 'paymillerror' => '1', 'paymillpayment' => $this->payment)));
		}
		elseif (!in_array($this->payment, $valid_payments))
		{
			$this->log('The selected Paymentmethod is not valid.', $this->payment);
			Tools::redirect($this->context->link->getPageLink('order', true, null, array('step' => '1', 'paymillerror' => '1', 'paymillpayment' => $this->payment)));
		}

		$this->log('Start processing payment with token', $this->token);

		foreach ($this->module->getCurrency((int)$this->context->cart->id_currency) as $currency)
		{
			if ($currency['id_currency'] == $this->context->cart->id_currency)
				$this->iso_currency = $currency['iso_code'];
		}
	}

	/**
	 * Payment controller action
	 */
	public function initContent()
	{
		$this->paymillInit();

		$result = $this->processPayment();
		if ($result === true)
		{
			$customer = new Customer((int)$this->context->cart->id_customer);
			$this->saveUserData(
					$this->payment_processor->getClientId(), $this->payment_processor->getPaymentId(), (int)$this->context->cart->id_customer
			);

			$payment_text = $this->getPaymentText();
			$this->context->cookie->__set('paymill_payment_text', $payment_text);
			$this->module->validateOrder(
					(int)$this->context->cart->id,
					Configuration::get('PIGMBH_PAYMILL_ORDERSTATE'),
					$this->context->cart->getOrderTotal(true, Cart::BOTH),
					$payment_text,
					null,
					array(),
					null,
					false,
					$customer->secure_key,
					$this->context->shop
			);

			$this->saveTransactionData(
					(int)$this->module->currentOrder, $this->payment_processor->getPreauthId(), $this->payment_processor->getTransactionId()
			);
			$this->updatePaymillTransaction(
					$this->payment_processor->getTransactionId(), Tools::substr('OrderID: '.(int)$this->module->currentOrder.' - Name:'.
							$this->context->customer->lastname.', '.$this->context->customer->firstname, 0, 128)
			);

			$url = $this->context->link->getPageLink(
				'order-confirmation',
				true,
				null,
				array(
					'key' => $customer->secure_key,
					'id_cart' => (int)$this->context->cart->id,
					'id_module' => (int)$this->module->id,
					'id_order' => (int)$this->module->currentOrder
				)
			);
			Tools::redirect($url);
			
		}
		else
		{
			$error_message = $this->module->errorCodeMapping($this->payment_processor->getErrorCode());
			$this->log('ErrorCode', $error_message);
			Tools::redirect($this->context->link->getPageLink('order', true, null, array('step' => '3', 'paymillerror' => '1', 'errorCode' => $this->payment_processor->getErrorCode())));
		}
	}

	/**
	 * Get payment text
	 * @return string
	 */
	private function getPaymentText()
	{
		$payment_text = '';
		if ($this->payment === 'debit')
		{
			$days = Configuration::get('PIGMBH_PAYMILL_DEBIT_DAYS');
			if (!is_numeric($days))
				$days = '7';

			$payment_text = $this->module->l('ELV /SEPA Debit Date: ').date('Y-m-d', strtotime('+'.$days.' day'));
		}
		else
			$payment_text = $this->module->l('Credit Card');
		return $payment_text;
	}

	/**
	 * Process payment
	 * @return boolean
	 */
	private function processPayment()
	{
		$this->payment_processor = new Services_Paymill_PaymentProcessor(
				Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/'
		);

		$this->payment_processor->setAmount((int)round($this->context->cart->getOrderTotal(true, Cart::BOTH) * 100));
		$this->payment_processor->setToken($this->token);
		$this->payment_processor->setCurrency(Tools::strtolower($this->iso_currency));
		$this->payment_processor->setName($this->context->customer->lastname.', '.$this->context->customer->firstname);
		$this->payment_processor->setEmail($this->context->customer->email);
		$this->payment_processor->setDescription('');
		$this->payment_processor->setLogger($this);
		$this->payment_processor->setSource(Configuration::get('PIGMBH_PAYMILL_VERSION').'_prestashop_core_'._PS_VERSION_);

		if ($this->payment == 'creditcard')
			$sql = 'SELECT `clientId`,`paymentId` FROM `'._DB_PREFIX_.'pigmbh_paymill_creditcard_userdata` WHERE `userId`='.
					(int)$this->context->customer->id;
		elseif ($this->payment == 'debit')
			$sql = 'SELECT `clientId`,`paymentId` FROM `'._DB_PREFIX_.'pigmbh_paymill_directdebit_userdata` WHERE `userId`='.
					(int)$this->context->customer->id;
		$user_data = $this->db->getRow($sql);
		$this->payment_processor->setClientId(!empty($user_data['clientId']) ? $user_data['clientId'] : null);

		if ($this->token === 'dummyToken')
			$this->payment_processor->setPaymentId(!empty($user_data['paymentId']) ? $user_data['paymentId'] : null);

		$capture_now = true;
		if ($this->payment == 'creditcard')
			$capture_now = Configuration::get('PIGMBH_PAYMILL_CAPTURE') !== 'on';
		$result = $this->payment_processor->processPayment($capture_now);

		$this->log('Payment processing resulted in', ($result ? 'Success' : 'Fail'));
		return $result;
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
				'identifier' => $this->log_id,
				'debug' => $db->escape($debug_info),
				'message' => $db->escape($message),
					), false, false, Db::INSERT, true);
		}
	}

	/**
	 * Save paymill client and/or payment id for returning customers
	 *
	 * @param string $client_id
	 * @param string $payment_id
	 * @param string $user_id
	 */
	private function saveUserData($client_id, $payment_id, $user_id)
	{
		$db = Db::getInstance();
		$table = Tools::getValue('payment') == 'creditcard' ? 'pigmbh_paymill_creditcard_userdata' : 'pigmbh_paymill_directdebit_userdata';
		try {
			$query = 'SELECT COUNT(*) as `count` FROM `'._DB_PREFIX_.$table.'` WHERE userId="'.(int)$user_id.'";';
			$count = $db->executeS($query, true);
			$count = (int)$count[0]['count'];
			if ($count === 0)
			{
				if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') !== 'on')
					$payment_id = null;
				$this->log('Inserted new data.', var_export(array($client_id, $payment_id, $user_id), true));
				$sql = 'INSERT INTO `'._DB_PREFIX_.$table.'` (`clientId`, `paymentId`, `userId`) VALUES("'.pSQL($client_id).'", "'.
						pSQL($payment_id).'", '.(int)$user_id.');';
			}
			elseif ($count === 1)
			{
				if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') === 'on')
				{
					$this->log('Updated User '.$client_id, var_export(array($client_id, $payment_id), true));
					$sql = 'UPDATE `'._DB_PREFIX_.$table.'` SET `clientId`="'.pSQL($client_id).'", `paymentId`="'.
							pSQL($payment_id).'" WHERE `userId`='.(int)$user_id;
				}
				else
				{
					$this->log('Updated User $client_id.', var_export(array($client_id), true));
					$sql = 'UPDATE `'._DB_PREFIX_.$table.'` SET `clientId`="'.pSQL($client_id).'" WHERE `userId`='.(int)$user_id;
				}
			}

			$db->execute($sql);
		} catch (Exception $exception) {
			$this->log('Failed saving UserData. ', $exception->getMessage());
		}
	}

	private function saveTransactionData($order_id, $preauth, $transaction)
	{
		$db = Db::getInstance();
		$sql = 'INSERT INTO `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` (`id`, `preauth`, `transaction`, `refund`) VALUES("'.
				(int)$order_id.'", "'.
				pSQL($preauth).'", "'.
				pSQL($transaction).'", 0);';
		try {
			$db->execute($sql);
		} catch (Exception $exception) {
			$this->log('Failed saving Transactiondata. ', $exception->getMessage());
		}
	}

	/**
	 * Update the paymill transaction with the given description
	 *
	 * @param string $transaction_id
	 * @param string $description
	 */
	private function updatePaymillTransaction($transaction_id, $description)
	{
		$transaction_object = new Services_Paymill_Transactions(
				Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/'
		);

		$transaction_object->update(array(
			'id' => $transaction_id,
			'description' => $description
		));
	}

}
