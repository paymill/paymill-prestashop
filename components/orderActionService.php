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
    
    private $logIdentifier;
    private $util;
    private $refund;
    private $transaction;
    private $apiEndpoint = 'https://api.paymill.com/v2/';


    public function __construct() {
	$privateKey = Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY');
	$this->util = new util();
	$this->refund = new Services_Paymill_Refunds($privateKey, $this->apiEndpoint);
	$this->transaction = new Services_Paymill_Transactions($privateKey, $this->apiEndpoint);
    }
    
    public function refund($orderId){
	$this->logIdentifier = time();
	if(!$this->util->isPaymillOrder($orderId))
	    return false;
	
	$data = $this->getTransactionData($orderId);
	try{
	    $result = $this->refund->create(array(
		'transactionId' => $data['transaction'],
		'params' => array(
		    'amount' => number_format($data['total_paid'], 2) * 100 
		)
	    ));

	    $returnValue = isset($result['response_code']) && $result['response_code'] === 20000;
	    $this->log('Refund resulted in ' . (string)$returnValue, var_export($result,true));
	    $db = Db::getInstance();
	    $db->execute('UPDATE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` SET `refund`=1');
	}catch(Exception $exception){
	    $this->log('Refund exception ', var_export($exception->getMessage(),true));
	    $returnValue = false;
	}
	if($returnValue){
            $dbResult = Db::getInstance()->executeS('SELECT `id_order_state` FROM `'._DB_PREFIX_
			.'order_state_lang` WHERE `template` = "refund" GROUP BY `template`;');
            $newOrderState = (int)$dbResult[0]['id_order_state'];
            $objOrder = new Order($orderId);
            $history = new OrderHistory();
            $history->id_order = (int)$objOrder->id;
            $history->changeIdOrderState($newOrderState, (int)($objOrder->id)); //order status=3
            $history->add(true);
        }
	return $returnValue;
    }
    
    public function capture($orderId){
	$this->logIdentifier = time();
	if(!$this->util->isPaymillOrder($orderId))
	    return false;
	
	$data = $this->getTransactionData($orderId);
	try{
	    $result = $this->transaction->create(array(
		'amount' => number_format($data['total_paid'], 2) * 100,
		'currency' => $data['iso_code'],
		'preauthorization' => $data['preauth'],
		'description' => 'OrderId: '. $orderId,
	    ));

	    $returnValue = isset($result['id']);
	    $this->log('Capture resulted in ' . var_export($returnValue,true), var_export($result,true));
	    $db = Db::getInstance();
	    if($returnValue)
		$db->execute('UPDATE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` SET `transaction`="'.$db->_escape($result['id']).'"');
	}catch(Exception $exception){
	    $this->log('Capture exception ', var_export($exception->getMessage(),true));
	    $returnValue = false;
	}
	return $returnValue;
    }
    
    private function getTransactionData($orderId){
	$db = Db::getInstance();
	$sql =	'SELECT `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.*, `reference`, `total_paid`, `iso_code` FROM `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` '.
		'LEFT JOIN `'._DB_PREFIX_.'orders` on `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.`id` = `'._DB_PREFIX_.'orders`.`id_order` '.
		'LEFT JOIN `'._DB_PREFIX_.'currency` on `'._DB_PREFIX_.'orders`.`id_currency` = `'._DB_PREFIX_.'currency`.`id_currency` '.
		'WHERE `'._DB_PREFIX_.'pigmbh_paymill_transactiondata`.`id`='.  (int)$orderId;
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
			   'identifier' => $this->logIdentifier,
			   'debug' => $db->escape($debug_info),
			   'message' => $db->escape($message),
			   ), false, false, Db::INSERT, true);
	   }
   }
    
}
