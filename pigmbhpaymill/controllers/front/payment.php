<?php

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
        parent::initContent();
        $cart = $this->context->cart;
        foreach ($this->module->getCurrency((int) $cart->id_currency) as $currency) {
            if ($currency["id_currency"] == $cart->id_currency) {
                $iso_currency = $currency["iso_code"];
                break;
            }
        }

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'currency_iso' => $iso_currency,
            'total' => intval($cart->getOrderTotal(true, Cart::BOTH) * 100),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'public_key' => Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'),
            'bridge_url' => Configuration::get('PIGMBH_PAYMILL_BRIDGEURL'),
            'payment' => Tools::getValue('payment'),
            'paymill_show_label' => Configuration::get('PIGMBH_PAYMILL_LABEL') == 'on',
            'paymill_debugging' => Configuration::get('PIGMBH_PAYMILL_DEBUG') == 'on',
            'components' => _PS_BASE_URL_.__PS_BASE_URI__."modules/pigmbhpaymill/components/",
            'customer' => $this->context->customer->firstname." ".$this->context->customer->lastname
        ));
        $this->setTemplate('paymentForm.tpl');
    }

}
