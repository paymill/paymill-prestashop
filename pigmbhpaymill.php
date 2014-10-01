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
if (!function_exists('curl_init'))
	exit;

class PigmbhPaymill extends PaymentModule
{
	/**
	 *
	 * @var ConfigurationHandler
	 */
	private $configuration_handler;

	/**
	 * Sets the Information for the Modulmanager
	 * Also creates an instance of this class
	 */
	public function __construct()
	{
		$this->name = 'pigmbhpaymill';
		$this->tab = 'payments_gateways';
		$this->version = '2.1.0';
		$this->author = 'PayIntelligent GmbH';
		$this->need_instance = 1;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		Configuration::updateValue('PIGMBH_PAYMILL_VERSION', $this->version);
		parent::__construct();

		$this->configuration_handler = new ConfigurationHandler();
		$this->displayName = $this->l('PAYMILL');
		$this->description = $this->l('Accept online payments easily in up to 100 currencies. Free download & testing!');
		//Adjust Modulname to the One use in Checkout, so the customer will be correctly redirected to the thank-you page
		if ($this->context->cookie->__isset('paymill_payment_text'))
            $this->displayName = $this->context->cookie->__get('paymill_payment_text');

	}

	/**
	 * This function installs the Module
	 *
	 * @return boolean
	 */
	public function install()
	{
		$this->warning = null;
		if (is_null($this->warning) && !function_exists('curl_init'))
			$this->warning = $this->l('cURL is required to use this module. Please install the php extention cURL.');
		if (is_null($this->warning) && !(parent::install()
			&& $this->registerHook('payment')
			&& $this->registerHook('paymentReturn')
			&& $this->registerHook('Header')
			&& $this->registerHook('paymentTop')))
			$this->warning = $this->l('There was an Error installing the module.');
		if (is_null($this->warning) && !$this->configuration_handler->setDefaultConfiguration())
			$this->warning = $this->l('There was an Error initiating the configuration.');
		if (is_null($this->warning) && !$this->createDatabaseTables())
			$this->warning = $this->l('There was an Error creating the database tables.');
		if (is_null($this->warning) && !$this->addPaymillOrderState())
			$this->warning = $this->l('There was an Error creating a custom orderstate.');

        $this->registerHook('displayPaymentEU');
		return is_null($this->warning);
	}

	/**
	 * This function deinstalls the Module
	 *
	 * @return boolean
	 */
	public function uninstall()
	{
		Configuration::deleteByName('PIGMBH_PAYMILL_ORDERSTATE', null);
		return $this->unregisterHook('payment') && $this->unregisterHook('paymentReturn') && $this->unregisterHook('paymentTop')
			&& $this->unregisterHook('Header')
			&& parent::uninstall();
	}

	/**
	 * Register the refund webhook
	 *
	 * @param string $private_key
	 * @return array
	 */
	private function registerPaymillWebhook($private_key)
	{
		$webhook = new Services_Paymill_Webhooks($private_key, 'https://api.paymill.com/v2/');
		return $webhook->create(array(
				'url' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/webHookEndpoint.php',
				'event_types' => array('refund.succeeded')
		));
	}

	/**
	 * Load CSS and JS into HTML Head-tag
	 */
	public function hookHeader()
	{
		if (!$this->active || $this->name !== Tools::getValue('module'))
			return;

		$this->context->controller->addCSS(__PS_BASE_URI__.'modules/pigmbhpaymill/css/paymill_styles.css');
		if (_PS_VERSION_ < '1.6')
			$this->context->controller->addCSS(__PS_BASE_URI__.'modules/pigmbhpaymill/css/paymill_checkout_1_5.css');

		$this->context->controller->addJS('https://bridge.paymill.com/');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/pigmbhpaymill/js/BrandDetection.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/pigmbhpaymill/js/Iban.js');
		$this->context->controller->addJS(__PS_BASE_URI__.'modules/pigmbhpaymill/js/PaymillCheckout.js');
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
			'valid_key' => !in_array(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), array('', null))
			&& !in_array(Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'), array('', null)),
		));
		$template = 'views/templates/hook/payment.tpl';
		if (_PS_VERSION_ < '1.6')
			$template = 'views/templates/hook/payment1_5.tpl';

		return $this->display(__FILE__, $template);
	}

	/**
	 * @return string
	 */
	public function hookdisplayPaymentEU()
	{
		if (!$this->active)
			return;

        $this->context->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'debit' => Configuration::get('PIGMBH_PAYMILL_DEBIT'),
			'creditcard' => Configuration::get('PIGMBH_PAYMILL_CREDITCARD'),
			'valid_key' => !in_array(Configuration::get('PIGMBH_PAYMILL_PRIVATEKEY'), array('', null))
			&& !in_array(Configuration::get('PIGMBH_PAYMILL_PUBLICKEY'), array('', null)),
		));

        return array(
                array(
                    'cta_text' => $this->l('Paymill Directdebit'),
                    'logo' => Media::getMediaPath(dirname(__FILE__).'/img/icon-hook.png'),
                    'action' => $this->context->link->getModuleLink($this->name, 'payment', array('payment'=>'debit'))
                ),
                array(
                    'cta_text' => $this->l('Paymill Creditcard'),
                    'logo' => Media::getMediaPath(dirname(__FILE__).'/img/icon-hook.png'),
                    'action' => $this->context->link->getModuleLink($this->name, 'payment', array('payment'=>'creditcard'))
                )
        );
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
			'modul_base' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/'
		));

		return $this->display(__FILE__, 'views/templates/hook/error.tpl');
	}

	/**
	 * @return string
	 */
	public function hookPaymentReturn()
	{
        if (!$this->active)
			return;
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

			$db->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pigmbh_paymill_logging` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`identifier` text NOT NULL,
				`debug` text NOT NULL,
				`message` text NOT NULL,
				`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
				) AUTO_INCREMENT=1'
			);

			$db->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pigmbh_paymill_directdebit_userdata` (
				`userId` int(11) NOT NULL,
				`clientId` text NOT NULL,
				`paymentId` text NOT NULL,
				PRIMARY KEY (`userId`)
				);'
			);

			$db->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pigmbh_paymill_creditcard_userdata` (
				`userId` int(11) NOT NULL,
				`clientId` text NOT NULL,
				`paymentId` text NOT NULL,
				PRIMARY KEY (`userId`)
				);'
			);

			return true;
		} catch (Exception $exception) {
			return false;
		}
	}

	private function onConfigurationSave()
	{
		$old_config = $this->configuration_handler->loadConfiguration();
		$new_config = new ConfigurationModel();
		$accepted_brands = array();
		foreach (Tools::getValue('accepted_brands') as $accepted_brand)
			$accepted_brands[$accepted_brand] = true;

		$accepted_brands_result = array();
		foreach (array_keys($old_config->getAccpetedCreditCards()) as $key)
		{
			if (array_key_exists($key, $accepted_brands))
				$accepted_brands_result[$key] = true;
			else
				$accepted_brands_result[$key] = false;
		}

		$new_config->setCreditcard(Tools::getValue('creditcard', 'OFF'));
		$new_config->setDirectdebit(Tools::getValue('debit', 'OFF'));
		$new_config->setDebug(Tools::getValue('debug', 'OFF'));
		$new_config->setFastcheckout(Tools::getValue('fastcheckout', 'OFF'));
		$new_config->setLogging(Tools::getValue('logging', 'OFF'));
		$new_config->setPrivateKey(trim(Tools::getValue('privatekey', $old_config->getPrivateKey())));
		$new_config->setPublicKey(trim(Tools::getValue('publickey', $old_config->getPublicKey())));
		$new_config->setAccpetedCreditCards($accepted_brands_result);
		$new_config->setDebitDays(Tools::getValue('debit_days', '7'));
		$this->configuration_handler->updateConfiguration($new_config);
		$this->registerPaymillWebhook($new_config->getPrivateKey());
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
			$this->onConfigurationSave();

		$configuration_model = $this->configuration_handler->loadConfiguration();

		//logging
		$db = Db::getInstance();
		$logdata = array();
		$detail_data = array();
		$show_detail = false;
		$search = $db->_escape(Tools::getValue('searchvalue', false));
		$connected_search = Tools::getValue('connectedsearch', 'off');
		$this->limit = 10;
		$where = $search && !empty($search) ? ' WHERE `debug` LIKE "%'.$search.'%" OR `message` LIKE "%'.$search.'%"' : null;
		$db->execute('SELECT * FROM `'._DB_PREFIX_.'pigmbh_paymill_logging`'.$where, true);
		$max_page = ceil($db->numRows() / $this->limit) == 0 ? 1 : range(1, ceil($db->numRows() / $this->limit));
		$page = $max_page < Tools::getValue('paymillpage', 1) ? $max_page : Tools::getValue('paymillpage', 1);
		$start = $page * $this->limit - $this->limit;

        $myaction = $this->context->link->getAdminLink('AdminModules', false);
		$myaction .= '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$myaction .= '&token='.Tools::getAdminTokenLite('AdminModules');

		//Details
		if (Tools::getValue('paymillid') && Tools::getValue('paymillkey'))
		{
			$show_detail = true;
			$row = $db->executeS('SELECT * FROM `'._DB_PREFIX_.'pigmbh_paymill_logging` WHERE id="'.$db->_escape(Tools::getValue('paymillid')).'";', true);
			$detail_data['title'] = 'DEBUG';
			$detail_data['data'] = $row[0]['debug'];
		}

		//getAll Data
		if ($connected_search === 'on')
			$where = 'WHERE `identifier` in(SELECT `identifier` FROM `'._DB_PREFIX_.'pigmbh_paymill_logging` '.$where.')';

		$sql = 'SELECT `id`,`identifier`,`date`,`message`,`debug` FROM `'._DB_PREFIX_.'pigmbh_paymill_logging` '.$where.' LIMIT '.$start.', '.$this->limit;
		foreach ($db->executeS($sql, true) as $row)
		{
			$unsorted_print_data = array();
			foreach ($row as $key => $value)
			{
				$value = is_array($value) ? $value[1].'<br><br>'.$value[0] : $value;
				$unsorted_print_data[$key] = $value;
                if(Tools::strlen($value) >= 250)
                    $unsorted_print_data['link'] = $myaction.'&paymillid='.$row['id'].'&paymillkey='.$key.'&searchvalue='.$search;
			}

			$logdata[] = $unsorted_print_data;
		}



		$this->context->smarty->assign(array(
			'include' => array(
				'css' => _PS_BASE_URL_.__PS_BASE_URI__.'modules/pigmbhpaymill/css/paymill_styles.css',
				'header' => dirname(__FILE__).'/views/templates/admin/paymillheader.tpl',
				'config' => dirname(__FILE__).'/views/templates/admin/paymillconfig.tpl',
				'log' => dirname(__FILE__).'/views/templates/admin/paymilllog.tpl',
			),
			'header' => array(
				'paymill_description' => 'Online payments made easy'
			),
			'config' => array(
				'action' => 	$myaction,
				'creditcard' => $this->getCheckboxState($configuration_model->getCreditcard()),
				'debit' => $this->getCheckboxState($configuration_model->getDirectdebit()),
				'privatekey' => $configuration_model->getPrivateKey(),
				'publickey' => $configuration_model->getPublicKey(),
				'debit_days' => $configuration_model->getDebitDays(),
				'debug' => $this->getCheckboxState($configuration_model->getDebug()),
				'logging' => $this->getCheckboxState($configuration_model->getLogging()),
				'fastcheckout' => $this->getCheckboxState($configuration_model->getFastcheckout()),
				'accepted_brands' => $configuration_model->getAccpetedCreditCards(),
			),
			'logging' => array(
				'data' => $logdata,
				'detail_data' => $detail_data,
				'show_detail' => $show_detail,
				'paymill_maxpage' => $max_page,
				'paymill_currentpage' => $page,
				'paymill_searchvalue' => $search,
				'paymill_connectedsearch' => $connected_search,
			)
		));

		return $this->display(__FILE__, 'views/templates/admin/adminpage.tpl');
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
		if (in_array(Tools::strtolower($value), array('on')))
			$return = 'checked';

		return $return;
	}

	/**
	 * Add paymill order state
	 * @return boolean
	 */
	private function addPaymillOrderState()
	{
		if (!Configuration::get('PIGMBH_PAYMILL_ORDERSTATE'))
		{
			$new_orderstate = new OrderState();
			$new_orderstate->name = array();
			$new_orderstate->module_name = $this->name;
			$new_orderstate->send_email = false;
			$new_orderstate->color = '#73E650';
			$new_orderstate->hidden = false;
			$new_orderstate->delivery = true;
			$new_orderstate->logable = true;
			$new_orderstate->invoice = true;
			$new_orderstate->paid = true;
			foreach (Language::getLanguages() as $language)
			{
				if (Tools::strtolower($language['iso_code']) == 'de')
					$new_orderstate->name[$language['id_lang']] = 'Bezahlung via PAYMILL erfolgreich';
				else
					$new_orderstate->name[$language['id_lang']] = 'Payment via PAYMILL successfully';
			}

			if ($new_orderstate->add())
			{
				$paymill_icon = dirname(__FILE__).'/img/20x20_orderstate.gif';
				$new_state_icon = dirname(__FILE__).'/../../img/os/'.(int)$new_orderstate->id.'.gif';
				copy($paymill_icon, $new_state_icon);
			}

			Configuration::updateValue('PIGMBH_PAYMILL_ORDERSTATE', (int)$new_orderstate->id);
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
		$error_messages = array(
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
			return array_key_exists($code, $error_messages) ? $error_messages[$code] : 'Unknown Error';
		else
			return 'Unknown Error';
	}

}
