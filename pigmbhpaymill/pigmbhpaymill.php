<?php

/**
 * PigmbhPaymill
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
if (!defined('_PS_VERSION_'))
    exit;

class PigmbhPaymill extends PaymentModule
{

    /**
     * Sets the Information for the Modulmanager
     * Also creates an instance of this class
     */
    public function __construct()
    {
        $this->name = 'pigmbhpaymill';
        $this->tab = 'payments_gateways';
        $this->version = "1.1.0";
        $this->author = 'PayIntelligent GmbH';
        $this->need_instance = 0;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        parent::__construct();
        $this->loadConfiguration();
        $this->displayName = $this->l('PigmbhPaymill');
        $this->description = $this->l('Payment via Paymill.');
    }

    /**
     * This function installs the Module
     *
     * @return boolean
     */
    public function install()
    {
        return parent::install() && $this->registerHook('payment') && $this->registerHook('paymentReturn') && $this->defaultConfiguration() && $this->createDatabaseTables();
    }

    /**
     * This function deinstalls the Module
     *
     * @return boolean
     */
    public function uninstall()
    {
        return $this->unregisterHook('payment') && $this->unregisterHook('paymentReturn') && parent::uninstall();
    }

    /**
     *
     * @param type $params
     * @return type
     */
    public function hookPayment($params)
    {
        if (!$this->active)
            return;

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'debit' => Configuration::get('PIGMBH_PAYMILL_DEBIT'),
            'creditcard' => Configuration::get('PIGMBH_PAYMILL_CREDITCARD')
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return;

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    public function createDatabaseTables()
    {
        $sqlDebit = "CREATE TABLE IF NOT EXISTS `pigmbh_paymill_directdebit_userdata` ( "
            . "`userId` int(11) NOT NULL, "
            . "`clientId` text NOT NULL, "
            . "`paymentId` text NOT NULL, "
            . "PRIMARY KEY (`userId`) "
            . ");";
        $sqlCreditCard = "CREATE TABLE IF NOT EXISTS `pigmbh_paymill_creditcard_userdata` ( "
            . "`userId` int(11) NOT NULL, "
            . "`clientId` text NOT NULL, "
            . "`paymentId` text NOT NULL, "
            . "PRIMARY KEY (`userId`) "
            . ");";
        $db = Db::getInstance();
        try {
            $db->query($sqlCreditCard);
            $db->query($sqlDebit);
            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * Returns the Pluginconfiguration as HTML-string
     *
     *
     * @return string HTML
     */
    public function getContent()
    {
        $this->_html = '<h2>' . $this->displayName . '</h2>';
        if (Tools::isSubmit('btnSubmit')) {
            $this->updateConfiguration();
        }
        $this->showConfigurationForm();
        return $this->_html;
    }

    private function showConfigurationForm()
    {
        $this->_html .=
            '<form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
			<fieldset>
			<legend>' . 'Konfiguration' . '</legend>
				<table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
					<tr><td width="130" style="height: 35px;">' . $this->l('Public Key') . '</td><td><input type="text" name="publickey" value="' . htmlentities(Tools::getValue('publickey', $this->publickey), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Private Key') . '</td><td><input type="text" name="privatekey" value="' . htmlentities(Tools::getValue('privatekey', $this->privatekey), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Bridge URL') . '</td><td><input type="text" name="bridgeurl" value="' . htmlentities(Tools::getValue('bridgeurl', $this->bridgeurl), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('API URL') . '</td><td><input type="text" name="apiurl" value="' . htmlentities(Tools::getValue('apiurl', $this->apiurl), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Activate debugging') . '</td><td><input type="checkbox" name="debug" ' . $this->getCheckboxState(htmlentities(Tools::getValue('debug', $this->debug), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Activate logging') . '</td><td><input type="checkbox" name="logging" ' . $this->getCheckboxState(htmlentities(Tools::getValue('logging', $this->logging), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Show Paymill label') . '</td><td><input type="checkbox" name="label" ' . $this->getCheckboxState(htmlentities(Tools::getValue('label', $this->label), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Activate debit-payment') . '</td><td><input type="checkbox" name="debit" ' . $this->getCheckboxState(htmlentities(Tools::getValue('debit', $this->debit), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Activate creditcard-payment') . '</td><td><input type="checkbox" name="creditcard" ' . $this->getCheckboxState(htmlentities(Tools::getValue('creditcard', $this->creditcard), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Activate fastCheckout') . '</td><td><input type="checkbox" name="fastcheckout" ' . $this->getCheckboxState(htmlentities(Tools::getValue('fastcheckout', $this->fastcheckout), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
                                        <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Save') . '" type="submit" /></td></tr>
                                        <tr><td colspan="2" style="height: 15px;"></td></tr>
                                        <tr><td colspan="2" align="center"><textarea style="width:600px;" rows="15" readonly />' . file_get_contents(dirname(__FILE__) . '/log.txt') . '</textarea></td></tr>
				</table>
			</fieldset>
		</form>';
    }

    private function getCheckboxState($value)
    {
        $return = '';
        if (in_array($value, array("on", true))) {
            $return = 'checked';
        }
        return $return;
    }

    private function updateConfiguration()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PIGMBH_PAYMILL_PUBLICKEY', Tools::getValue('publickey'));
            Configuration::updateValue('PIGMBH_PAYMILL_PRIVATEKEY', Tools::getValue('privatekey'));
            Configuration::updateValue('PIGMBH_PAYMILL_BRIDGEURL', Tools::getValue('bridgeurl'));
            Configuration::updateValue('PIGMBH_PAYMILL_APIURL', Tools::getValue('apiurl'));
            Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', Tools::getValue('debug'));
            Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', Tools::getValue('logging'));
            Configuration::updateValue('PIGMBH_PAYMILL_LABEL', Tools::getValue('label'));
            Configuration::updateValue('PIGMBH_PAYMILL_DEBIT', Tools::getValue('debit'));
            Configuration::updateValue('PIGMBH_PAYMILL_CREDITCARD', Tools::getValue('creditcard'));
            Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', Tools::getValue('fastcheckout'));
            $this->loadConfiguration();
            $this->_html .= '<div class="conf confirm"> ' . $this->l('Settings updated') . '</div>';
        }
    }

    private function defaultConfiguration()
    {
        Configuration::updateValue('PIGMBH_PAYMILL_BRIDGEURL', 'https://bridge.paymill.com/');
        Configuration::updateValue('PIGMBH_PAYMILL_APIURL', 'https://api.paymill.com/v2/');
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_LABEL', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_DEBIT', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_CREDITCARD', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', 'OFF');
        return true;
    }

    private function loadConfiguration()
    {
        $config = Configuration::getMultiple(
                array(
                    'PIGMBH_PAYMILL_PUBLICKEY',
                    'PIGMBH_PAYMILL_PRIVATEKEY',
                    'PIGMBH_PAYMILL_BRIDGEURL',
                    'PIGMBH_PAYMILL_APIURL',
                    'PIGMBH_PAYMILL_DEBUG',
                    'PIGMBH_PAYMILL_LOGGING',
                    'PIGMBH_PAYMILL_LABEL',
                    'PIGMBH_PAYMILL_DEBIT',
                    'PIGMBH_PAYMILL_CREDITCARD',
                    'PIGMBH_PAYMILL_FASTCHECKOUT'
                )
        );
        if (isset($config['PIGMBH_PAYMILL_PUBLICKEY'])) {
            $this->publickey = $config['PIGMBH_PAYMILL_PUBLICKEY'];
        }
        if (isset($config['PIGMBH_PAYMILL_PRIVATEKEY'])) {
            $this->privatekey = $config['PIGMBH_PAYMILL_PRIVATEKEY'];
        }
        if (isset($config['PIGMBH_PAYMILL_BRIDGEURL'])) {
            $this->bridgeurl = $config['PIGMBH_PAYMILL_BRIDGEURL'];
        }
        if (isset($config['PIGMBH_PAYMILL_APIURL'])) {
            $this->apiurl = $config['PIGMBH_PAYMILL_APIURL'];
        }
        if (isset($config['PIGMBH_PAYMILL_DEBUG'])) {
            $this->debug = $config['PIGMBH_PAYMILL_DEBUG'];
        }
        if (isset($config['PIGMBH_PAYMILL_LOGGING'])) {
            $this->logging = $config['PIGMBH_PAYMILL_LOGGING'];
        }
        if (isset($config['PIGMBH_PAYMILL_LABEL'])) {
            $this->label = $config['PIGMBH_PAYMILL_LABEL'];
        }
        if (isset($config['PIGMBH_PAYMILL_DEBIT'])) {
            $this->debit = $config['PIGMBH_PAYMILL_DEBIT'];
        }
        if (isset($config['PIGMBH_PAYMILL_CREDITCARD'])) {
            $this->creditcard = $config['PIGMBH_PAYMILL_CREDITCARD'];
        }
        if (isset($config['PIGMBH_PAYMILL_FASTCHECKOUT'])) {
            $this->fastcheckout = $config['PIGMBH_PAYMILL_FASTCHECKOUT'];
        }
    }

}
