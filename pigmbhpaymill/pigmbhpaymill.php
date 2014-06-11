<?php
/**
* 2012-2014 PAYMILL
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
*
*  @author    PAYMILL <support@paymill.com>
*  @copyright 2012-2014 PAYMILL
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/components/configurationHandler.php');
require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/components/models/configurationModel.php');
require_once(_PS_ROOT_DIR_.'/modules/pigmbhpaymill/paymill/v2/lib/Services/Paymill/Webhooks.php');

/**
 * PigmbhPaymill
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
if (!defined('_PS_VERSION_'))
	exit;

if (!function_exists('curl_init'))
	exit;

class PigmbhPaymill extends PaymentModule
{
	/**
	 *
	 * @var configurationHandler
	 */
	private $_configurationHandler;

	/**
	 * Sets the Information for the Modulmanager
	 * Also creates an instance of this class
	 */
	public function __construct()
	{
		if (session_id() == '')
			session_start();


		if (isset($_SESSION['piOrderId']) && Tools::getValue('id_order') == $_SESSION['piOrderId'])
		{
			$name = $_SESSION['piPaymentText'];
			unset($_SESSION['piPaymentText']);
			unset($_SESSION['piOrderId']);
		}
		else
		{
			$name = $this->l('PigmbhPaymill');
		}

		$this->name = 'pigmbhpaymill';
		$this->tab = 'payments_gateways';
		$this->version = '1.4.0';
		$this->author = 'PayIntelligent GmbH';
		$this->need_instance = 1;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		Configuration::updateValue('PIGMBH_PAYMILL_VERSION', $this->version);
		parent::__construct();

		$this->_configurationHandler = new configurationHandler();
		$this->displayName = $name;
		$this->description = $this->l('Payment via Paymill.');
	}

	/**
	 * Create the order
	 *
	 * @param type $id_cart
	 * @param type $id_order_state
	 * @param type $amount_paid
	 * @param type $payment_method
	 * @param type $message
	 * @param type $extra_vars
	 * @param type $currency_special
	 * @param type $dont_touch_amount
	 * @param type $secure_key
	 * @param Shop $shop
	 * @return boolean
	 */
	public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown', $message = NULL, $extra_vars = array(), $currency_special = NULL, $dont_touch_amount = false, $secure_key = false, Shop $shop = null)
	{
		$returnValue = null;
		if (parent::validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop))
		{
			$returnValue = $this->currentOrder;
		}

		return $returnValue;
	}

	/**
	 * Update the order state
	 *
	 * @param int $order_id
	 */
	public function updateOrderState($order_id)
	{
		$result = Db::getInstance()->executeS('SELECT `id_order_state` FROM `'._DB_PREFIX_.'order_state_lang` WHERE `template` = "refund" GROUP BY `template`;');
		$sql = 'INSERT INTO `'._DB_PREFIX_.'order_history` (`id_employee`,`id_order`,`id_order_state`,`date_add`) VALUES (0,%d, %d, NOW());';
		$order_state_id = (int) $result[0]['id_order_state'];
		$secure_sql = sprintf($sql, $order_id, $order_state_id);
		Db::getInstance()->execute($secure_sql);
	}

	/**
	 * This function installs the Module
	 *
	 * @return boolean
	 */
	public function install()
	{
		return parent::install() && $this->registerHook('payment') && $this->registerHook('paymentReturn') && $this->registerHook('paymentTop') && $this->_configurationHandler->setDefaultConfiguration() && $this->createDatabaseTables() && $this->_addPaymillOrderState();
	}

	/**
	 * This function deinstalls the Module
	 *
	 * @return boolean
	 */
	public function uninstall()
	{
		Configuration::deleteByName('PIGMBH_PAYMILL_ORDERSTATE', null);
		return $this->unregisterHook('payment') && $this->unregisterHook('paymentReturn') && $this->unregisterHook('paymentTop') && parent::uninstall();
	}

	/**
	 * Register the refund webhook
	 *
	 * @param string $privateKey
	 * @return array
	 */
	private function registerPaymillWebhook($privateKey)
	{
		$webHook = new Services_Paymill_Webhooks($privateKey, 'https://api.paymill.com/v2/');
		return $webHook->create(array(
				'url' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/webHookEndpoint.php',
				'event_types' => array('refund.succeeded')
		));
	}

	/**
	 * @return string
	 */
	public function hookPayment()
	{
		if (!$this->active)
			return;

		$this->context->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'debit' => Configuration::get('PIGMBH_PAYMILL_DEBIT'),
			'creditcard' => Configuration::get('PIGMBH_PAYMILL_CREDITCARD'),
			'valid_key' => !in_array(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), array('', null)) && !in_array(Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'), array('', null)),
		));

		return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
	}

	/**
	 * @return string
	 */
	public function hookPaymentTop()
	{
		if (!$this->active && Tools::getValue('paymillerror') != 1)
			return;

		$this->context->smarty->assign(array(
			'paymillerror' => Tools::getValue('paymillerror') == 1 ? $this->l('Payment could not be processed.') : null,
			'errormessage' => $this->errorCodeMapping(Tools::getValue('errorCode')),
			'components' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/components/'
		));

		return $this->display(__FILE__, 'views/templates/hook/error.tpl');
	}

	/**
	 * @return string
	 */
	public function hookPaymentReturn()
	{
		if (!$this->active)
		{
			return;
		}

		return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
	}

	/**
	 * Create all needed tables
	 *
	 * @return boolean
	 */
	public function createDatabaseTables()
	{
		try {
			$db = Db::getInstance();

			$db->execute('CREATE TABLE IF NOT EXISTS `pigmbh_paymill_logging` ('
				.'`id` int(11) NOT NULL AUTO_INCREMENT,'
				.'`identifier` text NOT NULL,'
				.'`debug` text NOT NULL,'
				.'`message` text NOT NULL,'
				.'`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,'
				.'PRIMARY KEY (`id`)'
				.') AUTO_INCREMENT=1'
			);

			$db->execute('CREATE TABLE IF NOT EXISTS `pigmbh_paymill_directdebit_userdata` ( '
				.'`userId` int(11) NOT NULL, '
				.'`clientId` text NOT NULL, '
				.'`paymentId` text NOT NULL, '
				.'PRIMARY KEY (`userId`) '
				.');'
			);

			$db->execute('CREATE TABLE IF NOT EXISTS `pigmbh_paymill_creditcard_userdata` ( '
				.'`userId` int(11) NOT NULL, '
				.'`clientId` text NOT NULL, '
				.'`paymentId` text NOT NULL, '
				.'PRIMARY KEY (`userId`) '
				.');'
			);

			return true;
		} catch (Exception $exception) {
			return false;
		}
	}

	private function onConfigurationSave()
	{
		$oldConfig = $this->_configurationHandler->loadConfiguration();
		$newConfig = new configurationModel();
		$toleranz = Tools::getValue('differentamount');
		if (is_numeric($toleranz))
			$toleranz = number_format($toleranz, 2, '.', '');
		else
			$toleranz = number_format(0, 2, '.', '');

		$acceptedBrands = array();
		foreach (Tools::getValue('accepted_brands') as $acceptedBrand)
			$acceptedBrands[$acceptedBrand] = true;

		$acceptedBrandsResult = array();

		foreach ($oldConfig->getAccpetedCreditCards() as $key => $value) {
			if (array_key_exists($key, $acceptedBrands))
				$acceptedBrandsResult[$key] = true;
			else
				$acceptedBrandsResult[$key] = false;
		}

		$newConfig->setCreditcard(Tools::getValue('creditcard', 'OFF'));
		$newConfig->setDirectdebit(Tools::getValue('debit', 'OFF'));
		$newConfig->setDebug(Tools::getValue('debug', 'OFF'));
		$newConfig->setFastcheckout(Tools::getValue('fastcheckout', 'OFF'));
		$newConfig->setLogging(Tools::getValue('logging', 'OFF'));
		$newConfig->setPrivateKey(trim(Tools::getValue('privatekey', $oldConfig->getPrivateKey())));
		$newConfig->setPublicKey(trim(Tools::getValue('publickey', $oldConfig->getPublicKey())));
		$newConfig->setAccpetedCreditCards($acceptedBrandsResult);
		$newConfig->setDebitDays(Tools::getValue('debit_days', '7'));
		$this->_configurationHandler->updateConfiguration($newConfig);
		$this->registerPaymillWebhook($newConfig->getPrivateKey());
	}

	/**
	 * Returns the Pluginconfiguration as HTML-string
	 *
	 * @return string
	 */
	public function getContent()
	{
		//configuration
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->onConfigurationSave();
		}

		//logging
		$db = Db::getInstance();
		$data = array();
		$detail_data = array();
		$show_detail = false;
		$search = Tools::getValue('searchvalue', false);
		$connected_search = Tools::getValue('connectedsearch', "off");
		$this->limit = 10;
		$where = $search && !empty($search) ? ' WHERE `debug` LIKE "%'.$search.'%" OR `message` LIKE "%'.$search.'%"' : null;
		$db->execute('SELECT * FROM `pigmbh_paymill_logging`'.$where, true);
		$max_page = ceil($db->numRows() / $this->limit) == 0 ? 1 : range(1, ceil($db->numRows() / $this->limit));
		$page = $max_page < Tools::getValue('paymillpage', 1) ? $max_page : Tools::getValue('paymillpage', 1);
		$start = $page * $this->limit - $this->limit;

		//Details
		if (Tools::getValue('paymillid') && Tools::getValue('paymillkey'))
		{
			$show_detail = true;
			$row = $db->executeS('SELECT * FROM `pigmbh_paymill_logging` WHERE id="'.Tools::getValue('paymillid').'";', true);
			$detail_data['title'] = 'DEBUG';
			$detail_data['data'] = $row[0]['debug'];
		}

		//getAll Data
		if ($connected_search === "on")
			$where = 'WHERE `identifier` in(SELECT `identifier` FROM `pigmbh_paymill_logging` '.$where.')';

		$sql = 'SELECT `id`,`identifier`,`date`,`message`,`debug` FROM `pigmbh_paymill_logging` '.$where.' LIMIT '.$start.', '.$this->limit;
		foreach ($db->executeS($sql, true) as $row) {
			foreach ($row as $key => $value) {
				$value = is_array($value) ? $value[1]."<br><br>".$value[0] : $value;
				$unsorted_print_data[$key] = Tools::strlen($value) >= 300 ? '<a href="'.$_SERVER['REQUEST_URI'].'&paymillid='.$row['id'].'&paymillkey='.$key.'&searchvalue='.$search.'">'.$this->l('see more').'</a>' : $value;
			}

			$data[] = $unsorted_print_data;
		}

		$this->context->smarty->assign(array(
			'config' => $this->showConfigurationForm(),
			'data' => $data,
			'detailData' => $detail_data,
			'showDetail' => $show_detail,
			'paymillMaxPage' => $max_page,
			'paymillCurrentPage' => $page,
			'paymillSearchValue' => $search,
			'paymillConnectedSearch' => $connected_search
		));

		return $this->display(__FILE__, 'views/templates/admin/logging.tpl');
	}

	/**
	 * Get the configuration form html
	 * @return string
	 */
	private function showConfigurationForm()
	{
		$configuration_model = $this->_configurationHandler->loadConfiguration();
		return
			'<link rel="stylesheet" type="text/css" href="'._PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/css/paymill_styles.css">
        <form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
            <fieldset class="paymill_center">
                <legend>'.$this->displayName.'</legend>
                <table cellpadding="0" cellspacing="0">
                    <tr><td colspan="2" class="paymill_config_header">'.$this->l('config_payments').'</td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Activate creditcard-payment').'</td><td class="paymill_config_value"><input type="checkbox" name="creditcard" '.$this->getCheckboxState($configuration_model->getCreditcard()).' /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Activate debit-payment').'</td><td class="paymill_config_value"><input type="checkbox" name="debit" '.$this->getCheckboxState($configuration_model->getDirectdebit()).' /></td></tr>
                    <tr><td colspan="2" style="height: 15px;"></td></tr>
                    <tr><td colspan="2" class="paymill_config_header">'.$this->l('config_main').'</td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Private Key').'</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="privatekey" value="'.$configuration_model->getPrivateKey().'" /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Public Key').'</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="publickey" value="'.$configuration_model->getPublicKey().'" /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Days until the debit').'</td><td class="paymill_config_value"><input type="text" class="paymill_config_text" name="debit_days" value="'.$configuration_model->getDebitDays().'" /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Activate debugging').'</td><td class="paymill_config_value"><input type="checkbox" name="debug" '.$this->getCheckboxState($configuration_model->getDebug()).' /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Activate logging').'</td><td class="paymill_config_value"><input type="checkbox" name="logging" '.$this->getCheckboxState($configuration_model->getLogging()).' /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Activate fastCheckout').'</td><td class="paymill_config_value"><input type="checkbox" name="fastcheckout" '.$this->getCheckboxState($configuration_model->getFastcheckout()).' /></td></tr>
                    <tr><td class="paymill_config_label">'.$this->l('Accepted CreditCard Brands').'</td><td class="paymill_config_value"><select multiple name="accepted_brands[]">'.$this->_getAccepetdBrandOptions($configuration_model).'</select></td></tr>
                    <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="'.$this->l('Save').'" type="submit" /></td></tr>
                </table>
            </fieldset>
        </form>';
	}

	/**
	 * @param configurationModel $configurationModel
	 * @return string
	 */
	private function _getAccepetdBrandOptions(configurationModel $configurationModel)
	{
		$html = '';
		foreach ($configurationModel->getAccpetedCreditCards() as $brand => $selected) {
			$selectedHtml = $selected ? 'selected' : '';
			$html .= '<option value="'.$brand.'" '.$selectedHtml.'>'.$brand.'</option>';
		}

		return $html;
	}

	/**
	 * Get the checkbox state
	 *
	 * @param string $value
	 * @return string
	 */
	private function getCheckboxState($value)
	{
		$return = '';
		if (in_array(Tools::strtolower($value), array("on")))
			$return = 'checked';

		return $return;
	}

	/**
	 * Add paymill order state
	 * @return boolean
	 */
	private function _addPaymillOrderState()
	{
		if (!Configuration::get('PIGMBH_PAYMILL_ORDERSTATE'))
		{
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
				if (Tools::strtolower($language['iso_code']) == 'de')
					$newOrderState->name[$language['id_lang']] = 'Bezahlung via PAYMILL erfolgreich';
				else
					$newOrderState->name[$language['id_lang']] = 'Payment via PAYMILL successfully';
			}

			if ($newOrderState->add())
			{
				$paymillIcon = dirname(__FILE__).'/logo.gif';
				$newStateIcon = dirname(__FILE__).'/../../img/os/'.(int) $newOrderState->id.'.gif';
				copy($paymillIcon, $newStateIcon);
			}

			Configuration::updateValue('PIGMBH_PAYMILL_ORDERSTATE', (int) $newOrderState->id);
		}

		return true;
	}

	/**
	 * Get error code map
	 * @param string $code
	 * @return string
	 */
	public function errorCodeMapping($code)
	{
		$errorMessages = array(
			'10001' => $this->l('General undefined response.'),
			'10002' => $this->l('Still waiting on something.'),
			'20000' => $this->l('General success response.'),
			'40000' => $this->l('General problem with data.'),
			'40001' => $this->l('General problem with payment data.'),
			'40100' => $this->l('Problem with credit card data.'),
			'40101' => $this->l('Problem with cvv.'),
			'40102' => $this->l('Card expired or not yet valid.'),
			'40103' => $this->l('Limit exceeded.'),
			'40104' => $this->l('Card invalid.'),
			'40105' => $this->l('Expiry date not valid.'),
			'40106' => $this->l('Credit card brand required.'),
			'40200' => $this->l('Problem with bank account data.'),
			'40201' => $this->l('Bank account data combination mismatch.'),
			'40202' => $this->l('User authentication failed.'),
			'40300' => $this->l('Problem with 3d secure data.'),
			'40301' => $this->l('Currency / amount mismatch'),
			'40400' => $this->l('Problem with input data.'),
			'40401' => $this->l('Amount too low or zero.'),
			'40402' => $this->l('Usage field too long.'),
			'40403' => $this->l('Currency not allowed.'),
			'50000' => $this->l('General problem with backend.'),
			'50001' => $this->l('Country blacklisted.'),
			'50100' => $this->l('Technical error with credit card.'),
			'50101' => $this->l('Error limit exceeded.'),
			'50102' => $this->l('Card declined by authorization system.'),
			'50103' => $this->l('Manipulation or stolen card.'),
			'50104' => $this->l('Card restricted.'),
			'50105' => $this->l('Invalid card configuration data.'),
			'50200' => $this->l('Technical error with bank account.'),
			'50201' => $this->l('Card blacklisted.'),
			'50300' => $this->l('Technical error with 3D secure.'),
			'50400' => $this->l('Decline because of risk issues.'),
			'50500' => $this->l('General timeout.'),
			'50501' => $this->l('Timeout on side of the acquirer.'),
			'50502' => $this->l('Risk management transaction timeout.'),
			'50600' => $this->l('Duplicate transaction.')
		);

		if (is_null($code))
			return array_key_exists($code, $errorMessages) ? $errorMessages[$code] : 'Unknown Error';
		else
			return 'Unknown Error';
	}

}
