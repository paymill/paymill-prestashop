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
        $db = Db::getInstance();
        session_start();
        $validPayments = array();
        if (Configuration::get('PIGMBH_PAYMILL_DEBIT')) {
            $validPayments[] = 'debit';
        }
        if (Configuration::get('PIGMBH_PAYMILL_CREDITCARD')) {
            $validPayments[] = 'creditcard';
        }

        if (!in_array(Tools::getValue('payment'), $validPayments)) {
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');
        }

        $dbData = array();
        if (isset($this->context->customer->id)) {
            if (Tools::getValue('payment') == 'creditcard') {
                $sql = 'SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id;
            } elseif (Tools::getValue('payment') == 'debit') {
                $sql = 'SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id;
            }
            try {
                $dbData = $db->getRow($sql);
            } catch (Exception $exception) {
                $dbData = false;
            }
        }
        if ($dbData && $this->validateClient($dbData['clientId'])) {
            $clientObject = new Services_Paymill_Clients(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
            $oldClient = $clientObject->getOne($dbData['clientId']);
            if ($this->context->customer->email !== $oldClient['email']) {
                $clientObject->update(array(
                    'id' => $dbData['clientId'],
                    'email' => $this->context->customer->email
                    )
                );
            }
        }

        $payment = false;
        if ($dbData && $this->validatePayment($dbData['paymentId'])) {
            $paymentObject = new Services_Paymill_Payments(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
            $paymentResponse = $paymentObject->getOne($dbData['paymentId']);
            if ($paymentResponse['id'] === $dbData['paymentId']) {
                $payment = $dbData['paymentId'] !== '' ? $paymentResponse : false;
            }
            $payment['expire_date'] = null;
            if (isset($payment['expire_month'])) {
                $payment['expire_month'] = $payment['expire_month'] <= 9 ? '0' . $payment['expire_month'] : $payment['expire_month'];
                $payment['expire_date'] = $payment['expire_month'] . "/" . $payment['expire_year'];
            }
        }
        $cart = $this->context->cart;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
                break;
            }
        }

        $_SESSION['pigmbhPaymill']['authorizedAmount'] = (int) round($cart->getOrderTotal(true, Cart::BOTH) * 100);


        $brands = array();

        foreach (json_decode(Configuration::get('PIGMBH_PAYMILL_ACCEPTED_BRANDS'), true) as $brandKey => $brandValue) {
            $brands[str_replace('-', '', $brandKey)] = $brandValue;
        }
        
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
            'prefilledFormData' => $payment,
            'acceptedBrands' => Configuration::get('PIGMBH_PAYMILL_ACCEPTED_BRANDS'),
            'acceptedBrandsDecoded' => $brands
        );

        $this->context->smarty->assign($data);
        parent::initContent();
        $this->setTemplate('paymentForm.tpl');
    }

    private function validateClient($clientId)
    {
        $clientObject = new Services_Paymill_Clients(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
        return $this->validatePaymillId($clientObject, $clientId);
    }

    private function validatePayment($paymentId)
    {
        $paymentObject = new Services_Paymill_Payments(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
        return $this->validatePaymillId($paymentObject, $paymentId);
    }

    private function validatePaymillId($object, $id)
    {
        $isValid = false;
        $objectResult = $object->getOne($id);
        if (array_key_exists('id', $objectResult)) {
            $isValid = $id === $objectResult['id'];
        }
        return $isValid;
    }

}
