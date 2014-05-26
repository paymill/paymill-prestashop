<?php

require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/Clients.php';
require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/Payments.php';

/**
 * PaymentController
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
class PigmbhpaymillPaymentModuleFrontController extends ModuleFrontController
{

    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_center = true;
        $this->display_column_right = false;
        session_start();
        $valid_payments = array();
        if (Configuration::get('PIGMBH_PAYMILL_DEBIT'))
            $valid_payments[] = 'debit';
        if (Configuration::get('PIGMBH_PAYMILL_CREDITCARD'))
            $valid_payments[] = 'creditcard';
        if (!in_array(Tools::getValue('payment'), $valid_payments)) 
            Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
        
        $db_data = $this->getPaymillUserData();
        
        $this->updatePaymillClient($db_data);

        $cart = $this->context->cart;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
                break;
            }
        }

        $_SESSION['pigmbhPaymill']['authorizedAmount'] = (int) round($cart->getOrderTotal(true, Cart::BOTH) * 100);
        
        $brands = array();
        foreach (json_decode(Configuration::get('PIGMBH_PAYMILL_ACCEPTED_BRANDS'), true) as $brand_key => $brand_value)
            $brands[str_replace('-', '', $brand_key)] = $brand_value;
        
        $data = array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'currency_iso' => $iso_currency,
            'total' => $_SESSION['pigmbhPaymill']['authorizedAmount'],
            'displayTotal' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
            'public_key' => Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'),
            'payment' => Tools::getValue('payment'),
            'paymill_debugging' => Configuration::get('PIGMBH_PAYMILL_DEBUG') == 'on',
            'components' => _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/pigmbhpaymill/components/',
            'customer' => $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
            'prefilledFormData' => $this->updatePaymillPayment($db_data),
            'acceptedBrands' => Configuration::get('PIGMBH_PAYMILL_ACCEPTED_BRANDS'),
            'acceptedBrandsDecoded' => $brands
        );

        $this->context->smarty->assign($data);
        parent::initContent();
        $this->setTemplate('paymentForm.tpl');
    }
    
    /**
     * Update paymill payment data if necessary
     * 
     * @param array $db_data
     * @return boolean|array
     */
    private function updatePaymillPayment($db_data)
    {
        $payment = false;
        if ($db_data && $this->validatePayment($db_data['paymentId'])) {
            $payment_object = new Services_Paymill_Payments(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/');
            $payment_response = $payment_object->getOne($db_data['paymentId']);
            if ($payment_response['id'] === $db_data['paymentId'])
                $payment = $db_data['paymentId'] !== '' ? $payment_response : false;
            $payment['expire_date'] = null;
            if (isset($payment['expire_month'])) {
                $payment['expire_month'] = $payment['expire_month'] <= 9 ? '0' . $payment['expire_month'] : $payment['expire_month'];
                $payment['expire_date'] = $payment['expire_month'] . "/" . $payment['expire_year'];
            }
        }
        
        return $payment;
    }
    
    /**
     * Update paymill client data if necessary
     * 
     * @param array $db_data
     */
    private function updatePaymillClient($db_data)
    {
        if ($db_data && $this->validateClient($db_data['clientId'])) {
            $client_object = new Services_Paymill_Clients(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/');
            $old_client = $client_object->getOne($db_data['clientId']);
            if ($this->context->customer->email !== $old_client['email']) {
                $client_object->update(array(
                    'id' => $db_data['clientId'],
                    'email' => $this->context->customer->email
                    )
                );
            }
        }
    }
    
    /**
     * Selects paymill client and payment id from database
     * 
     * @return array
     */
    private function getPaymillUserData()
    {
        $db = Db::getInstance();
        $db_data = array();
        if (isset($this->context->customer->id)) {
            if (Tools::getValue('payment') == 'creditcard') {
                $sql = 'SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id;
            } elseif (Tools::getValue('payment') == 'debit') {
                $sql = 'SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id;
            }
            try {
                $db_data = $db->getRow($sql);
            } catch (Exception $exception) {
                $db_data = array();
            }
        }
        
        return $db_data;
    }
    
    /**
     * Validates if paymill client id exists
     * 
     * @param string $client_id
     * @return boolean
     */
    private function validateClient($client_id)
    {
        $client_object = new Services_Paymill_Clients(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/');
        return $this->validatePaymillId($client_object, $client_id);
    }

    /**
     * Validates if paymill payment id exists
     * 
     * @param string $payment_id
     * @return boolean
     */
    private function validatePayment($payment_id)
    {
        $payment_object = new Services_Paymill_Payments(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), 'https://api.paymill.com/v2/');
        return $this->validatePaymillId($payment_object, $payment_id);
    }

    /**
     * Validates if paymill id exists
     * 
     * @param mixed $object
     * @param string $id
     * @return boolean
     */
    private function validatePaymillId($object, $id)
    {
        $is_valid = false;
        $object_result = $object->getOne($id);
        if (array_key_exists('id', $object_result)) {
            $is_valid = $id === $object_result['id'];
        }
        return $is_valid;
    }

}
