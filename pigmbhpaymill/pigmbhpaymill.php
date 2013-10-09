<?php

require_once 'components/configurationHandler.php';
require_once 'components/models/configurationModel.php';

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

    private $_configurationHandler;

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
        $this->need_instance = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        Configuration::updateValue('PIGMBH_PAYMILL_VERSION', $this->version);
        parent::__construct();

        $this->_configurationHandler = new configurationHandler();
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
        return parent::install() && $this->registerHook('payment') && $this->registerHook('paymentReturn') && $this->_configurationHandler->setDefaultConfiguration() && $this->createDatabaseTables() && $this->_addPaymillOrderState();
    }

    /**
     * This function deinstalls the Module
     *
     * @return boolean
     */
    public function uninstall()
    {
        Configuration::deleteByName('PIGMBH_PAYMILL_ORDERSTATE', null);
        return $this->unregisterHook('payment') && $this->unregisterHook('paymentReturn') && parent::uninstall();
    }

    /**
     *
     * @param type $params
     * @return type
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'debit' => Configuration::get('PIGMBH_PAYMILL_DEBIT'),
            'creditcard' => Configuration::get('PIGMBH_PAYMILL_CREDITCARD'),
            'valid_key' => !in_array(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), array('', null)) && !in_array(Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'), array('', null)),
            'paymillerror' => Tools::getValue('paymillerror') == 1 ? $this->l('Payment could not be processed.') : null,
            'paymillpayment' => Tools::getValue('paymillpayment')
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        return $this->display(__FILE__, 'confirmation.tpl');
    }

    public function createDatabaseTables()
    {
        $sqlLog = "CREATE TABLE IF NOT EXISTS `pigmbh_paymill_logging` ("
            . "`id` int(11) NOT NULL AUTO_INCREMENT,"
            . "`identifier` text NOT NULL,"
            . "`debug` text NOT NULL,"
            . "`message` text NOT NULL,"
            . "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
            . "PRIMARY KEY (`id`)"
            . ") AUTO_INCREMENT=1";

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
            $db->query($sqlLog);
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
        //configuration
        if (Tools::isSubmit('btnSubmit')) {
            $oldConfig = $this->_configurationHandler->loadConfiguration();
            $newConfig = new configurationModel();
            $toleranz = Tools::getValue('differentamount');
            if (is_numeric($toleranz)) {
                $toleranz = number_format($toleranz, 2, '.', '');
            } else {
                $toleranz = number_format(0, 2, '.', '');
            }
            $newConfig->setCreditcard(Tools::getValue('creditcard', 'OFF'));
            $newConfig->setDirectdebit(Tools::getValue('debit', 'OFF'));
            $newConfig->setDebug(Tools::getValue('debug', 'OFF'));
            $newConfig->setDifferentAmount($toleranz);
            $newConfig->setFastcheckout(Tools::getValue('fastcheckout', 'OFF'));
            $newConfig->setLabel(Tools::getValue('label', 'OFF'));
            $newConfig->setLogging(Tools::getValue('logging', 'OFF'));
            $newConfig->setPrivateKey(trim(Tools::getValue('privatekey', $oldConfig->getPrivateKey())));
            $newConfig->setPublicKey(trim(Tools::getValue('publickey', $oldConfig->getPublicKey())));
            $this->_configurationHandler->updateConfiguration($newConfig);
        }

        //logging
        $db = Db::getInstance();
        $data = array();
        $detailData = array();
        $showDetail = false;
        $search = Tools::getValue('searchvalue', false);
        $connectedSearch = Tools::getValue('connectedsearch', "off");
        $limit = 10;
        $where = $search && !empty($search) ? " WHERE `debug` LIKE '%" . $search . "%' OR `message` LIKE '%" . $search . "%'" : null;
        $db->execute("SELECT * FROM `pigmbh_paymill_logging`" . $where, true);
        $maxPage = ceil($db->numRows() / $limit) == 0 ? 1 : range(1, ceil($db->numRows() / $limit));
        $page = $maxPage < Tools::getValue('paymillpage', 1) ? $maxPage : Tools::getValue('paymillpage', 1);
        $start = $page * $limit - $limit;

        //Details
        if (Tools::getValue('paymillid') && Tools::getValue('paymillkey')) {
            $showDetail = true;
            $row = $db->executeS("SELECT * FROM `pigmbh_paymill_logging` WHERE id='" . Tools::getValue('paymillid') . "';", true);
            $detailData['title'] = 'DEBUG';
            $detailData['data'] = $row[0]['debug'];
        }

        //getAll Data
        if ($connectedSearch === "on") {
            $where = "WHERE `identifier` in(SELECT `identifier` FROM `pigmbh_paymill_logging` $where)";
        }
        $sql = "SELECT `id`,`identifier`,`date`,`message`,`debug` FROM `pigmbh_paymill_logging` $where LIMIT $start, $limit";
        foreach ($db->executeS($sql, true) as $row) {
            foreach ($row as $key => $value) {
                $value = is_array($value) ? $value[1] . "<br><br>" . $value[0] : $value;
                $unsortedPrintData[$key] = strlen($value) >= 300 ? "<a href='" . $_SERVER['REQUEST_URI'] . "&paymillid=" . $row['id'] . "&paymillkey=" . $key . "&searchvalue=" . $search . "'>" . $this->l('see more') . "</a>" : $value;
            }
            $data[] = $unsortedPrintData;
        }

        $this->smarty->assign(array(
            'config' => $this->showConfigurationForm(),
            'data' => $data,
            'detailData' => $detailData,
            'showDetail' => $showDetail,
            'paymillMaxPage' => $maxPage,
            'paymillCurrentPage' => $page,
            'paymillSearchValue' => $search,
            'paymillConnectedSearch' => $connectedSearch
        ));

        return $this->display(__FILE__, 'views/templates/admin/logging.tpl');
    }

    private function showConfigurationForm()
    {
        $configurationModel = $this->_configurationHandler->loadConfiguration();
        return
            '<link rel="stylesheet" type="text/css" href="' . _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/pigmbhpaymill/components/paymill_styles.css">
            <form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
			<fieldset class="paymill_center">
			<legend>' . $this->displayName . '</legend>
				<table cellpadding="0" cellspacing="0">
                    <tr><td colspan="2" class="paymill_config_header">' . $this->l('config_payments') . '</td></tr>
                    <tr><td class="paymill_config_label">' . $this->l('Activate creditcard-payment') . '</td><td class="paymill_config_value"><input type="checkbox" name="creditcard" ' . $this->getCheckboxState($configurationModel->getCreditcard()) . ' /></td></tr>
                    <tr><td class="paymill_config_label">' . $this->l('Activate debit-payment') . '</td><td class="paymill_config_value"><input type="checkbox" name="debit" ' . $this->getCheckboxState($configurationModel->getDirectdebit()) . ' /></td></tr>
                    <tr><td colspan="2" style="height: 15px;"></td></tr>
                    <tr><td colspan="2" class="paymill_config_header">' . $this->l('config_main') . '</td></tr>
                    <tr><td class="paymill_config_label">' . $this->l('Public Key') . '</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="publickey" value="' . $configurationModel->getPublicKey() . '" /></td></tr>
					<tr><td class="paymill_config_label">' . $this->l('Private Key') . '</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="privatekey" value="' . $configurationModel->getPrivateKey() . '" /></td></tr>
					<tr><td class="paymill_config_label">' . $this->l('differentAmount') . '</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="differentamount" value="' . $configurationModel->getDifferentAmount() . '" /></td></tr>
					<tr><td class="paymill_config_label">' . $this->l('Activate debugging') . '</td><td class="paymill_config_value"><input type="checkbox" name="debug" ' . $this->getCheckboxState($configurationModel->getDebug()) . ' /></td></tr>
					<tr><td class="paymill_config_label">' . $this->l('Activate logging') . '</td><td class="paymill_config_value"><input type="checkbox" name="logging" ' . $this->getCheckboxState($configurationModel->getLogging()) . ' /></td></tr>
					<tr><td class="paymill_config_label">' . $this->l('Show Paymill label') . '</td><td class="paymill_config_value"><input type="checkbox" name="label" ' . $this->getCheckboxState($configurationModel->getLabel()) . ' /></td></tr>
                    <tr><td class="paymill_config_label">' . $this->l('Activate fastCheckout') . '</td><td class="paymill_config_value"><input type="checkbox" name="fastcheckout" ' . $this->getCheckboxState($configurationModel->getFastcheckout()) . ' /></td></tr>
                    <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Save') . '" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
    }

    private function getCheckboxState($value)
    {
        $return = '';
        if (in_array(strtolower($value), array("on"))) {
            $return = 'checked';
        }
        return $return;
    }

    private function _addPaymillOrderState()
    {
        if (!Configuration::get('PIGMBH_PAYMILL_ORDERSTATE')) {
            $newOrderState = new OrderState();
            $newOrderState->name = array();
            $newOrderState->module_name = $this->name;
            $newOrderState->send_email = false;
            $newOrderState->color = '#73E650';
            $newOrderState->hidden = false;
            $newOrderState->delivery = true;
            $newOrderState->logable = true;
            $newOrderState->invoice = true;
            $newOrderState->paid = true;
            foreach (Language::getLanguages() as $language) {
                if (strtolower($language['iso_code']) == 'de'){
                    $newOrderState->name[$language['id_lang']] = 'Bezahlung via PAYMILL erfolgreich';
                }else{
                    $newOrderState->name[$language['id_lang']] = 'Payment via PAYMILL successfully';
                }
            }

            if ($newOrderState->add()) {
                $paymillIcon = dirname(__FILE__) . '/logo.gif';
                $newStateIcon = dirname(__FILE__) . '/../../img/os/' . (int) $newOrderState->id . '.gif';
                copy($paymillIcon, $newStateIcon);
            }
            Configuration::updateValue('PIGMBH_PAYMILL_ORDERSTATE', (int) $newOrderState->id);
        }
        return true;
    }

}
