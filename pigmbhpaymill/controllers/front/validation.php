<?php
require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/PaymentProcessor.php';
require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/LoggingInterface.php';
require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/Transactions.php';

/**
 * validation
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
class PigmbhpaymillValidationModuleFrontController extends ModuleFrontController implements Services_Paymill_LoggingInterface
{

    public function initContent()
    {
        
        session_start();
        unset($_SESSION['log_id']);
        $_SESSION['log_id'] = time();
        $db = Db::getInstance();
        $token = Tools::getValue('paymillToken');
        $payment = Tools::getValue('payment');
        $validPayments = array();
        if (Configuration::get('PIGMBH_PAYMILL_DEBIT')) {
            $validPayments[] = 'debit';
        }
        if (Configuration::get('PIGMBH_PAYMILL_CREDITCARD')) {
            $validPayments[] = 'creditcard';
        }
        if (empty($token)) {
            $this->log('No paymill token was provided. Redirect to payments page.', null);
            Tools::redirect('index.php?controller=order&step=1&paymillerror=1&paymillpayment=' . $payment);
        } elseif (!in_array($payment, $validPayments)) {
            $this->log('The selected Paymentmethod is not valid.', $payment);
            Tools::redirect('index.php?controller=order&step=1&paymillerror=1&paymillpayment=' . $payment);
        }
        $this->log('Start processing payment with token', $token);


        $paymentProcessor = new Services_Paymill_PaymentProcessor(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
        
        $cart = $this->context->cart;
        $user = $this->context->customer;
        $shop = $this->context->shop;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
            }
        }

        $paymentProcessor->setAmount($_SESSION['pigmbhPaymill']['authorizedAmount']);
        $paymentProcessor->setPreAuthAmount($_SESSION['pigmbhPaymill']['authorizedAmount']);
        $paymentProcessor->setToken($token);
        $paymentProcessor->setCurrency(strtolower($iso_currency));
        $paymentProcessor->setName($user->lastname . ', ' . $user->firstname);
        $paymentProcessor->setEmail($user->email);
        $paymentProcessor->setDescription(" ");
        $paymentProcessor->setLogger($this);
        $paymentProcessor->setSource(Configuration::get('PIGMBH_PAYMILL_VERSION') . "_prestashop_" . _PS_VERSION_);
        if ($payment == 'creditcard') {
            $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id);
        } elseif ($payment == 'debit') {
            $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id);
        }

        $paymentProcessor->setClientId(!empty($userData['clientId']) ? $userData['clientId'] : null);
        if ($token === "dummyToken") {
            $paymentProcessor->setPaymentId(!empty($userData['paymentId']) ? $userData['paymentId'] : null);
        }
        
        $result = $paymentProcessor->processPayment();
        
        $this->log(
            'Payment processing resulted in'
            , ($result ? 'Success' : 'Fail')
        );
        
        $paymill = $this->module;
        // finish the order if payment was sucessfully processed
        
        if ($result === true) {
            $customer = new Customer((int) $cart->id_customer);
            $this->saveUserData($paymentProcessor->getClientId(), $paymentProcessor->getPaymentId(), (int) $cart->id_customer);
            $paymentText = '';
            if ($payment === 'debit') {
                
                $days = Configuration::get('PIGMBH_PAYMILL_DEBIT_DAYS');
                if (!is_numeric($days)) {
                    $days = '7';
                }
                
                $paymentText = $paymill->l('ELV /SEPA Debit Date: ') . date('Y-m-d', strtotime("+$days day"));
            } else {
                $paymentText = $paymill->l('Credit Card');
            }
            
            $orderID = $paymill->validateOrder(
                (int) $cart->id, Configuration::get('PIGMBH_PAYMILL_ORDERSTATE'), $cart->getOrderTotal(true, Cart::BOTH), $paymentText , null, array(), null, false, $customer->secure_key, $shop);
            $this->updatePaymillTransaction($paymentProcessor->getTransactionId(), 'OrderID: ' . $orderID . ' - Name:' . $user->lastname . ', ' . $user->firstname);

            Tools::redirect('index.php?controller=order-confirmation?key=' . $customer->secure_key . '&id_cart=' . (int) $cart->id . '&id_module=' . (int) $paymill->id . '&id_order=' . (int) $paymill->currentOrder);
        } else {
            $errorMessage = $paymill->errorCodeMapping($paymentProcessor->getErrorCode());
            $this->log('ErrorCode', $errorMessage);
            Tools::redirect('index.php?controller=order&step=3&paymillerror=1&errorCode=' . $paymentProcessor->getErrorCode());
        }
    }

    public function log($message, $debugInfo)
    {
        $db = Db::getInstance();
        if (Configuration::get('PIGMBH_PAYMILL_LOGGING') === 'on') {
            try {
                $db->insert('pigmbh_paymill_logging', array(
                 'identifier'=>$_SESSION["log_id"],
                 'debug'=>$debugInfo,
                 'message'=>$message,
                ), false, false, Db::INSERT, false);
            } catch (exception $e) {
                
            }
        }
    }

    private function saveUserData($clientId, $paymentId, $userId)
    {
        $db = Db::getInstance();
        $table = Tools::getValue('payment') == 'creditcard' ? 'pigmbh_paymill_creditcard_userdata' : 'pigmbh_paymill_directdebit_userdata';
        try {
            $query = 'SELECT COUNT(*) as `count` FROM '.$table.' WHERE clientId="' . $clientId . '";';
            $count = $db->executeS($query,true);
            $count = (int)$count[0]['count'];
            if ($count === 0) {
                //insert
                $this->log("Inserted new data.", var_export(array($clientId, $paymentId, $userId), true));
                $sql = "INSERT INTO `$table` (`clientId`, `paymentId`, `userId`) VALUES('$clientId', '$paymentId', $userId);";
            } elseif ($count === 1) {
                //update
                if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') === 'on') {
                    $this->log("Updated User $userId.", var_export(array($clientId, $paymentId), true));
                    $sql = "UPDATE `$table` SET `clientId`='$clientId', `paymentId`='$paymentId' WHERE `userId`=$userId";
                } else {
                    $this->log("Updated User $userId.", var_export(array($clientId), true));
                    $sql = "UPDATE `$table` SET `clientId`='$clientId' WHERE `userId`=$userId";
                }
            }
            $db->execute($sql);
        } catch (Exception $exception) {
            $this->log("Failed saving UserData. ", $exception->getMessage());
        }
    }

    private function updatePaymillTransaction($transactionID, $description)
    {
        $transactionObject = new Services_Paymill_Transactions(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
        $transactionObject->update(array(
            'id' => $transactionID,
            'description' => $description
        ));
    }

}
