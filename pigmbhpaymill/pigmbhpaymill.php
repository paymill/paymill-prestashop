<?php

if (!defined('_PS_VERSION_'))
    exit;

class PigmbhPaymill extends PaymentModule
{

    public function __construct()
    {
        $this->name = 'pigmbhpaymill';
        $this->tab = 'Test';
        $this->version = 1.0;
        $this->author = 'PayIntelligent';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('PigmbhPaymill');
        $this->description = $this->l('Payments via Paymill.');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('payment')) {
            return false;
        }
        return true;
    }

    public function hookPayment($params)
    {
        if (!$this->active)
            return;

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

}
