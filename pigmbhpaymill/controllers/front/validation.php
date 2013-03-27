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
        $api_url = Configuration::get('PIGMBH_PAYMILL_APIURL');
        $private_key = Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY');
        $libBase = dirname(__FILE__) . '/../../paymill/v2/lib/';

        $cart = $this->context->cart;
        $user = $this->context->customer;
        $shop = $this->context->shop;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
                break;
            }
        }
        $processData = array(
            'authorizedAmount' => $_SESSION['pigmbhPaymill']['authorizedAmount'],
            'token' => $token,
            'amount' => (int) ($cart->getOrderTotal(true, Cart::BOTH) * 100),
            'currency' => $iso_currency,
            'name' => $user->lastname . ', ' . $user->firstname,
            'email' => $user->email,
            'description' => $shop->name . ' ' . $user->email,
            'libBase' => $libBase,
            'privateKey' => $private_key,
            'apiUrl' => $api_url
        );

        if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT')) {
            if ($payment == 'creditcard') {
                $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id);
            } elseif ($payment == 'debit') {
                $userData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id);
            }
            if (!empty($userData['clientId']) && !empty($userData['paymentId'])) {
                $processData['clientId'] = $userData['clientId'];
                $processData['paymentId'] = $userData['paymentId'];
            }
        }

        // process the payment
        $result = $this->processPayment($processData);

        $this->log(
                'Payment processing resulted in: '
                . ($result ? 'Success' : 'Fail')
        );
        // finish the order if payment was sucessfully processed
        if ($result === true) {
            $this->log('Finish order.');
            $this->module->validateOrder(
                    (int) $this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, null, array(), null, false, $user->secure_key);
            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $user->secure_key . '&id_cart=' . (int) $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . (int) $this->module->currentOrder);
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
        $transactionParams = array(
            'amount' => $params['authorizedAmount'],
            'currency' => $params['currency'],
            'description' => $params['description']
        );
        require_once $params['libBase'] . 'Services/Paymill/Transactions.php';
        require_once $params['libBase'] . 'Services/Paymill/Clients.php';
        require_once $params['libBase'] . 'Services/Paymill/Payments.php';

        $clientsObject = new Services_Paymill_Clients(
                        $params['privateKey'], $params['apiUrl']
        );
        $transactionsObject = new Services_Paymill_Transactions(
                        $params['privateKey'], $params['apiUrl']
        );

        $paymentObject = new Services_Paymill_Payments(
                        $params['privateKey'], $params['apiUrl']
        );
        // perform conection to the Paymill API and trigger the payment
        try {
            if (!array_key_exists('paymentId', $params)) {
                $payment = $paymentObject->create($payment_params);
                if (!isset($payment['id'])) {
                    $this->log('No Payment created: ' . var_export($payment, true));
                    return false;
                } else {
                    $this->log('Payment created: ' . $payment['id']);
                }
            } else {
                $payment['id'] = $params['paymentId'];
                $this->log('Saved payment used: ' . $params['paymentId']);
            }


            if (!array_key_exists('clientId', $params)) {
                // create client
                $client_params['creditcard'] = $payment['id'];
                $client = $clientsObject->create($client_params);
                if (!isset($client['id'])) {
                    $this->log('No client created: ' . var_export($client, true));
                    return false;
                } else {
                    $this->log('Client created: ' . $client['id']);
                }
            } else {
                $client['id'] = $params['clientId'];
                $this->log('Saved client used: ' . $params['clientId']);
            }

            // create transaction
            $transactionParams['client'] = $client['id'];
            $transactionParams['payment'] = $payment['id'];
            $transaction = $transactionsObject->create($transactionParams);
            if (!$this->confirmTransaction($transaction)) {
                return false;
            }
            if (!array_key_exists('clientId', $params) && !array_key_exists('paymentId', $params)) {
                $this->saveUserData($client['id'], $payment['id']);
            }
            if ($params['authorizedAmount'] !== $params['amount']) {
                if ($params['authorizedAmount'] > $params['amount']) {
                    require_once $params['libBase'] . 'Services/Paymill/Refunds.php';
                    // basketamount is lower than the authorized amount
                    $refundObject = new Services_Paymill_Refunds(
                                    $params['privateKey'], $params['apiUrl']
                    );
                    $refundTransaction = $refundObject->create(
                            array(
                                'transactionId' => $transaction['id'],
                                'params' => array(
                                    'amount' => $params['authorizedAmount'] - $params['amount']
                                )
                            )
                    );
                    if (isset($refundTransaction['data']['response_code']) && $refundTransaction['data']['response_code'] !== 20000) {
                        $this->log("An Error occured: " . var_export($refundTransaction, true));
                        return false;
                    }
                    if (!isset($refundTransaction['data']['id'])) {
                        $this->log("No Refund created" . var_export($refundTransaction, true));
                        return false;
                    } else {
                        $this->log("Refund created: " . $refundTransaction['data']['id']);
                    }
                } else {
                    // basketamount is higher than the authorized amount (paymentfee etc.)
                    $secoundTransactionParams = array(
                        'amount' => $params['amount'] - $params['authorizedAmount'],
                        'currency' => $params['currency'],
                        'description' => $params['description']
                    );
                    $secoundTransactionParams['client'] = $client['id'];
                    $secoundTransactionParams['payment'] = $payment['id'];
                    if (!$this->confirmTransaction($transactionsObject->create($secoundTransactionParams))) {
                        return false;
                    }
                }
            }
            return true;
        } catch (Services_Paymill_Exception $ex) {
            // paymill wrapper threw an exception
            $this->log('Exception thrown from paymill wrapper: ' . $ex->getMessage());
            return false;
        }
        return true;
    }

    private function log($message)
    {
        $logging = Configuration::get('PIGMBH_PAYMILL_LOGGING');
        $log_file = dirname(__FILE__) . '/../../log.txt';
        if (is_writable($log_file) && $logging == 'on') {
            $handle = fopen($log_file, 'a'); //
            fwrite($handle, '[' . date(DATE_RFC822) . '] ' . $message . "\n");
            fclose($handle);
        }
    }

    private function confirmTransaction($transaction)
    {
        if (isset($transaction['data']['response_code'])) {
            $this->log("An Error occured: " . var_export($transaction, true));
            return false;
        }
        if (!isset($transaction['id'])) {
            $this->log("No transaction created: " . var_export($transaction, true));
            return false;
        } else {
            $this->log("Transaction created: " . $transaction['id']);
        }

        // check result
        if (is_array($transaction) && array_key_exists('status', $transaction)) {
            if ($transaction['status'] == "open") {
                // transaction was issued but status is open for any reason
                $this->log("Status is open.");
                return false;
            } elseif ($transaction['status'] != "closed") {
                // another error occured
                $this->log("Unknown error." . var_export($transaction, true));
                return false;
            }
        } else {
            // another error occured
            $this->log("Transaction could not be issued.");
            return false;
        }
        return true;
    }

    private function saveUserData($clientId, $paymentId)
    {
        if (Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT')) {
            $db = Db::getInstance();
            $userId = $this->context->customer->id;
            $payment = Tools::getValue('payment');
            if ($payment == 'creditcard') {
                $table = 'pigmbh_paymill_creditcard_userdata';
            } elseif ($payment == 'debit') {
                $table = 'pigmbh_paymill_directdebit_userdata';
            }
            $data = array(
                'clientId' => $clientId,
                'paymentId' => $paymentId,
                'userId' => $userId
            );
            try {
                $db->insert($table, $data, false, false, Db::REPLACE, false);
                $this->log("UserData saved." . var_export($data, true));
            } catch (Exception $exception) {
                $this->log("Failed saving UserData. " . $exception->getMessage());
            }
        }
    }

}
