<?php

/**
 * PaymentController
 *
 * @category   PayIntelligent
 * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
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
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
            'publickey' => Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'),
            'bridgeurl' => Configuration::get('PIGMBH_PAYMILL_BRIDGEURL'),
            'payment' => Tools::getValue('payment'),
            'paymillShowLabel' => Configuration::get('PIGMBH_PAYMILL_LABEL') == 'on',
            'components' => _PS_BASE_URL_ . __PS_BASE_URI__ ."modules/pigmbhpaymill/components/"
        ));
        $this->setTemplate('paymentForm.tpl');
    }

}
