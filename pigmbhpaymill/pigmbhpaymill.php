<?php

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
        $this->version = '1.0';
        $this->author = 'PayIntelligent GmbH';
        $this->need_instance = 0;

        parent::__construct();
        $this->_loadConfiguration();
        $this->displayName = $this->l('PigmbhPaymill');
        $this->description = $this->l('Payments via Paymill.');
    }

    /**
     * This function installs the Module
     *
     * @return boolean
     */
    public function install()
    {
        return !parent::install() || !$this->registerHook('payment');
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
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
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
            $this->_updateConfiguration();
        }
        $this->_showConfigurationForm();
        return $this->_html;
    }

    private function _showConfigurationForm()
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
					<tr><td width="130" style="height: 35px;">' . $this->l('Debugging aktivieren') . '</td><td><input type="checkbox" name="debug" ' . $this->_getCheckboxState(htmlentities(Tools::getValue('debug', $this->debug), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Logging aktivieren') . '</td><td><input type="checkbox" name="logging" ' . $this->_getCheckboxState(htmlentities(Tools::getValue('logging', $this->logging), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
					<tr><td width="130" style="height: 35px;">' . $this->l('Paymill Label anzeigen') . '</td><td><input type="checkbox" name="label" ' . $this->_getCheckboxState(htmlentities(Tools::getValue('label', $this->label), ENT_COMPAT, 'UTF-8')) . ' style="width: 300px;" /></td></tr>
                                        <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Speichern') . '" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
    }

    private function _getCheckboxState($value)
    {
        $return = '';
        if (in_array($value, array("on", true))  ) {
            $return = 'checked';
        }
        return $return;
    }

    private function _updateConfiguration()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PIGMBH_PAYMILL_PUBLICKEY', Tools::getValue('publickey'));
            Configuration::updateValue('PIGMBH_PAYMILL_PRIVATEKEY', Tools::getValue('privatekey'));
            Configuration::updateValue('PIGMBH_PAYMILL_BRIDGEURL', Tools::getValue('bridgeurl'));
            Configuration::updateValue('PIGMBH_PAYMILL_APIURL', Tools::getValue('apiurl'));
            Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', Tools::getValue('debug'));
            Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', Tools::getValue('logging'));
            Configuration::updateValue('PIGMBH_PAYMILL_LABEL', Tools::getValue('label'));
            $this->_loadConfiguration();
            $this->_html .= '<div class="conf confirm"> ' . $this->l('Settings updated') . '</div>';
        }
    }

    private function _loadConfiguration()
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
    }

}
