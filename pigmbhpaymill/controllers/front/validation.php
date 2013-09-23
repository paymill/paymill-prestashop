<?php

require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/PaymentProcessor.php';
require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/LoggingInterface.php';

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
            $this->log('No paymill token was provided. Redirect to payments page.');
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        } elseif (!in_array($payment, $validPayments)) {
            $this->log('The selected Paymentmethod is not valid.(' . $payment . ')');
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $this->log('Start processing payment with token ' . $token);


        $paymentProcessor = new Services_Paymill_PaymentProcessor(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");

        $cart = $this->context->cart;
        $user = $this->context->customer;
        $shop = $this->context->shop;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
                break;
            }
        }

        $paymentProcessor->setAmount((int) ($cart->getOrderTotal(true, Cart::BOTH) * 100));
        $paymentProcessor->setPreAuthAmount($_SESSION['pigmbhPaymill']['authorizedAmount']);
        $paymentProcessor->setToken($token);
        $paymentProcessor->setCurrency(strtolower($iso_currency));
        $paymentProcessor->setName($user->lastname . ', ' . $user->firstname);
        $paymentProcessor->setEmail($user->email);
        $paymentProcessor->setDescription($shop->name . ' ' . $user->email);
        $paymentProcessor->setLogger($this);
        $paymentProcessor->setSource(Configuration::get('PIGMBH_PAYMILL_VERSION') . "_prestashop_" . _PS_VERSION_);
        if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') === 'on') {
            if ($payment == 'creditcard') {
                $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id);
            } elseif ($payment == 'debit') {
                $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id);
            }
            if (!empty($userData['clientId']) && !empty($userData['paymentId'])) {
                $paymentProcessor->setClientId($userData['clientId']);
                $paymentProcessor->setPaymentId($userData['paymentId']);
            }
        }

        $result = $paymentProcessor->processPayment();
        $this->log(
            'Payment processing resulted in: '
            . ($result ? 'Success' : 'Fail')
        );

        // finish the order if payment was sucessfully processed
        if ($result === true) {
            $this->log('Finish order.');
            $this->saveUserData($paymentProcessor->getClientId(), $paymentProcessor->getPaymentId());
            $this->module->validateOrder(
                (int) $this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null, array(), null, false, $user->secure_key);
            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $user->secure_key . '&id_cart=' . (int) $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . (int) $this->module->currentOrder);
        } else {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
    }

    public function log($message, $debugInfo)
    {
        $logging = Configuration::get('PIGMBH_PAYMILL_LOGGING');
        $log_file = dirname(__FILE__) . '/../../log.txt';
        if (is_writable($log_file) && $logging == 'on') {
            $handle = fopen($log_file, 'a'); //
            fwrite($handle, '[' . date(DATE_RFC822) . '] ' . $message . "::" . $debugInfo . "\n");
            fclose($handle);
        }
    }

    private function saveUserData($clientId, $paymentId)
    {
        $db = Db::getInstance();
        $userId = $this->context->customer->id;
        $table = Tools::getValue('payment') == 'creditcard' ? 'pigmbh_paymill_creditcard_userdata' : 'pigmbh_paymill_directdebit_userdata';
        $data['clientId'] = $clientId;

        //change payment only when fastchekout is active
        if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') === 'on') {
            $data['paymentId'] = $paymentId;
        }

        try {
            $count = $db->query(sprintf("SELECT COUNT(*) FROM %s WHERE ´clientId´=%s;", array(
                $table, $clientId
            )));
            $this->log("Count:", var_export($count, true));
            if (count($count) !== 0 || !$count) {
                //insert
                $this->log("Insert new data.", null);
                $data['userId'] = $userId;
                $db->insert($table, $data, true, false, DB::INSERT, false);
            } else {
                //update
                $this->log("Update data.", null);
                $db->update($table, $data, '´userId´=' . $userId);
            }
            $this->log("UserData saved.", var_export($data, true));
        } catch (Exception $exception) {
            $this->log("Failed saving UserData. " . $exception->getMessage());
        }
    }

}
