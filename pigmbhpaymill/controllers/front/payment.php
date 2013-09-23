<?php

require_once dirname(__FILE__) . '/../../paymill/v2/lib/Services/Paymill/Clients.php';

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
                $dbData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_creditcard_userdata` WHERE `userId`=' . $this->context->customer->id);
            } elseif (Tools::getValue('payment') == 'debit') {
                $dbData = $db->getRow('SELECT `clientId`,`paymentId` FROM `pigmbh_paymill_directdebit_userdata` WHERE `userId`=' . $this->context->customer->id);
            }
        }
        $fastCheckout = $dbData != false && count($dbData) > 0 && Configuration::get('PIGMBH_PAYMILL_FASTCHECKOUT') === 'on';
        $clientObject = new Services_Paymill_Clients(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), "https://api.paymill.com/v2/");
        if (isset($dbData['clientId'])) {
            $oldClient = $clientObject->getOne($dbData['clientId']);
            if ($this->context->customer->email !== $oldClient['email']) {
                $clientObject->update(array(
                    'id' => $dbData['clientId'],
                    'email' => $this->context->customer->email
                    )
                );
            }
        }
        $this->display_column_left = false;
        $this->display_column_center = true;
        $this->display_column_right = false;
        parent::initContent();
        $cart = $this->context->cart;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency['id_currency'] == $cart->id_currency) {
                $iso_currency = $currency['iso_code'];
                break;
            }
        }


        $_SESSION['pigmbhPaymill']['authorizedAmount'] = intval((Configuration::get('PIGMBH_PAYMILL_DIFFERENTAMOUNT') + $cart->getOrderTotal(true, Cart::BOTH)) * 100);
        $data = array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'currency_iso' => $iso_currency,
            'total' => $_SESSION['pigmbhPaymill']['authorizedAmount'],
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
            'public_key' => Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'),
            'payment' => Tools::getValue('payment'),
            'paymill_show_label' => Configuration::get('PIGMBH_PAYMILL_LABEL') == 'on',
            'paymill_debugging' => Configuration::get('PIGMBH_PAYMILL_DEBUG') == 'on',
            'components' => _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/pigmbhpaymill/components/',
            'customer' => $this->context->customer->firstname . ' ' . $this->context->customer->lastname,
            'paymill_form_year' => range(date('Y', time('now')), date('Y', time('now')) + 10),
            'paymill_form_month' => range(1, 12)
        );

        $this->context->smarty->assign($data);
        if ($fastCheckout) {
            $this->setTemplate('fastCheckout.tpl');
        } else {
            $this->setTemplate('paymentForm.tpl');
        }
    }

}
