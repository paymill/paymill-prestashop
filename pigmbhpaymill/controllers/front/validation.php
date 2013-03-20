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
        $token = Tools::getValue('paymillToken');
        $payment = Tools::getValue('payment');

        if (empty($token)) {
            $this->log('No paymill token was provided. Redirect to payments page.');
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        } elseif (!in_array($payment, array('creditcard', 'debit'))) {
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
        // process the payment
        $result = $this->processPayment(array(
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
                ));

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
        // setup the logger
        $logger = $params['loggerCallback'];

        $refund = false;
        $doubleTransaction = false;
        if ($params['authorizedAmount'] !== $params['amount']) {
            if ($params['authorizedAmount'] > $params['amount']) {
                // basketamount is lower than the authorized amount
                $refund = true;
                $refundParams = array(
                    'amount' => $params['authorizedAmount'] - $params['amount']
                );
                $this->log('PaymentMode: transaction & refund');
            } else {
                // basketamount is higher than the authorized amount (paymentfee etc.)
                $doubleTransaction = true;
                $secoundTransactionParams = array(
                    'amount' => $params['amount'] - $params['authorizedAmount'],
                    'currency' => $params['currency'],
                    'description' => $params['description']
                );
                $this->log('PaymentMode: double transaction');
            }
        } else {
            $this->log('PaymentMode: normal transaction');
        }

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
            $payment = $paymentObject->create($payment_params);
            if (!isset($payment['id'])) {
                $this->log('No Payment created: ' . var_export($payment, true));
                return false;
            } else {
                $this->log('Payment created: ' . $payment['id']);
            }
            // create client
            $client_params['creditcard'] = $payment['id'];
            $client = $clientsObject->create($client_params);
            if (!isset($client['id'])) {
                $this->log('No client created: ' . var_export($client, true));
                return false;
            } else {
                $this->log('Client created: ' . $client['id']);
            }
            // create transaction
            $transactionParams['client'] = $client['id'];
            $transactionParams['payment'] = $payment['id'];
            $transaction = $transactionsObject->create($transactionParams);
            if (!$this->confirmTransaction($transaction)) {
                return false;
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
                    $doubleTransaction = true;
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

}
