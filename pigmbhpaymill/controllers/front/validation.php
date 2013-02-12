<?php

/**
 * validation
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
class PigmbhpaymillValidationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $token = Tools::getValue('paymillToken');
        $payment = Tools::getValue('payment');

        if (empty($token)) {
            $this->log("No paymill token was provided. Redirect to payments page.");
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        } elseif (!in_array($payment, array("creditcard", "debit"))) {
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        }

        $this->log("Start processing payment with token " . $token);
        $api_url = Configuration::get('PIGMBH_PAYMILL_APIURL');
        $private_key = Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY');
        $libBase = dirname(__FILE__).'/../../paymill/v2/lib/';

        $cart = $this->context->cart;
        $user = $this->context->customer;
        $shop = $this->context->shop;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency["id_currency"] == $cart->id_currency) {
                $iso_currency = $currency["iso_code"];
                break;
            }
        }
        // process the payment
        $result = $this->processPayment(array(
            'libVersion' => 'v2',
            'token' => $token,
            'amount' => $cart->getOrderTotal(true, Cart::BOTH) * 100,
            'currency' => $iso_currency,
            'name' => $user->lastname.', '.$user->firstname,
            'email' => $user->email,
            'description' => $shop->name." ".$user->email,
            'libBase' => $libBase,
            'privateKey' => $private_key,
            'apiUrl' => $api_url,
            'loggerCallback' => array('PigmbhpaymillValidationModuleFrontController', 'log')
                ));

        $this->log(
                "Payment processing resulted in: "
                . ($result ? "Success" : "Fail")
        );
        // finish the order if payment was sucessfully processed
        if ($result === true) {
            $this->log("Finish order.");
            $this->module->validateOrder(
                    (int) $this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null, array(), null, false, $user->secure_key);
            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key='.$user->secure_key.'&id_cart='.(int) $cart->id.'&id_module='.(int) $this->module->id.'&id_order='.(int) $this->module->currentOrder);
        } else {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }
    }

    /**
     * Processes the payment against the paymill API
     * @param $params array The settings array
     * @return boolean
     */
    private function processPayment($params)
    {
        // setup the logger
        $logger = $params['loggerCallback'];

        // reformat paramters
        $params['currency'] = strtolower($params['currency']);
        // setup client params
        $client_params = array(
            'email' => $params['email'],
            'description' => $params['name']
        );
        // setup credit card params
        $payment_params = array(
            'token' => $params['token']
        );
        // setup transaction params
        $transaction_params = array(
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'description' => $params['description']
        );
        require_once $params['libBase'].'Services/Paymill/Transactions.php';
        require_once $params['libBase'].'Services/Paymill/Clients.php';

        $clients_object = new Services_Paymill_Clients(
                        $params['privateKey'], $params['apiUrl']
        );
        $transactions_object = new Services_Paymill_Transactions(
                        $params['privateKey'], $params['apiUrl']
        );
        if ($params['libVersion'] == 'v2') {
            require_once $params['libBase'].'Services/Paymill/Payments.php';
            $payment_object = new Services_Paymill_Payments(
                            $params['privateKey'], $params['apiUrl']
            );
        }
        // perform conection to the Paymill API and trigger the payment
        try {
            $payment = $payment_object->create($payment_params);
            if (!isset($payment['id'])) {
                call_user_func_array($logger, array("No creditcard created: ".var_export($payment, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Creditcard created: ".$payment['id']));
            }
            // create client
            $client_params['creditcard'] = $payment['id'];
            $client = $clients_object->create($client_params);
            if (!isset($client['id'])) {
                call_user_func_array($logger, array("No client created".var_export($client, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Client created: ".$client['id']));
            }
            // create transaction
            $transaction_params['client'] = $client['id'];
            if ($params['libVersion'] == 'v2') {
                $transaction_params['payment'] = $payment['id'];
            }
            $transaction = $transactions_object->create($transaction_params);
            if (!isset($transaction['id'])) {
                call_user_func_array($logger, array("No transaction created".var_export($transaction, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Transaction created: ".$transaction['id']));
            }
            // check result
            if (is_array($transaction) && array_key_exists('status', $transaction)) {
                if ($transaction['status'] == "closed") {
                    // transaction was successfully issued
                    return true;
                } elseif ($transaction['status'] == "open") {
                    // transaction was issued but status is open for any reason
                    call_user_func_array($logger, array("Status is open."));
                    return false;
                } else {
                    // another error occured
                    call_user_func_array($logger, array("Unknown error.".var_export($transaction, true)));
                    return false;
                }
            } else {
                // another error occured
                call_user_func_array($logger, array("Transaction could not be issued."));
                return false;
            }
        } catch (Services_Paymill_Exception $ex) {
            // paymill wrapper threw an exception
            call_user_func_array($logger, array("Exception thrown from paymill wrapper: ".$ex->getMessage()));
            return false;
        }
        return true;
    }

    static private function log($message)
    {
        $logging = Configuration::get('PIGMBH_PAYMILL_LOGGING');
        $log_file = dirname(__FILE__) . '/../../log.txt';
        if (is_writable($log_file) && $logging == 'on') {
            $handle = fopen($log_file, 'a'); //
            fwrite($handle, "[".date(DATE_RFC822)."] ".$message."\n");
            fclose($handle);
        }
    }

}
