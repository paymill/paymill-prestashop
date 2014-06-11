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

require_once 'models/configurationModel.php';

/**
 * configurationHandler
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class configurationHandler
{

    /**
     * Loads the configuration from the Database
     * @return configurationModel
     */
    public function loadConfiguration()
    {
        $configModel = new configurationModel();
        $config = Configuration::getMultiple(
            array(
                'PIGMBH_PAYMILL_PUBLICKEY',
                'PIGMBH_PAYMILL_PRIVATEKEY',
                'PIGMBH_PAYMILL_DEBIT_DAYS',
                'PIGMBH_PAYMILL_DEBUG',
                'PIGMBH_PAYMILL_LOGGING',
                'PIGMBH_PAYMILL_DEBIT',
                'PIGMBH_PAYMILL_CREDITCARD',
                'PIGMBH_PAYMILL_FASTCHECKOUT',
                'PIGMBH_PAYMILL_ACCEPTED_BRANDS',
            )
        );

        $configModel->setPublicKey(isset($config['PIGMBH_PAYMILL_PUBLICKEY']) ? $config['PIGMBH_PAYMILL_PUBLICKEY'] : '');
        $configModel->setPrivateKey(isset($config['PIGMBH_PAYMILL_PRIVATEKEY']) ? $config['PIGMBH_PAYMILL_PRIVATEKEY'] : '');
        $configModel->setDebitDays(isset($config['PIGMBH_PAYMILL_DEBIT_DAYS']) ? $config['PIGMBH_PAYMILL_DEBIT_DAYS'] : '');
        $configModel->setDebug(isset($config['PIGMBH_PAYMILL_DEBUG']) ? $config['PIGMBH_PAYMILL_DEBUG'] : false);
        $configModel->setLogging(isset($config['PIGMBH_PAYMILL_LOGGING']) ? $config['PIGMBH_PAYMILL_LOGGING'] : false);
        $configModel->setDirectdebit(isset($config['PIGMBH_PAYMILL_DEBIT']) ? $config['PIGMBH_PAYMILL_DEBIT'] : false);
        $configModel->setCreditcard(isset($config['PIGMBH_PAYMILL_CREDITCARD']) ? $config['PIGMBH_PAYMILL_CREDITCARD'] : false);
        $configModel->setFastcheckout(isset($config['PIGMBH_PAYMILL_FASTCHECKOUT']) ? $config['PIGMBH_PAYMILL_FASTCHECKOUT'] : false);
        $configModel->setAccpetedCreditCards(isset($config['PIGMBH_PAYMILL_ACCEPTED_BRANDS']) ? Tools::jsonDecode($config['PIGMBH_PAYMILL_ACCEPTED_BRANDS'], true) : false);
        return $configModel;
    }

    /**
     * Updates the Config and writes changes into db
     * @param configurationModel $model
     */
    public function updateConfiguration(configurationModel $model)
    {
        Configuration::updateValue('PIGMBH_PAYMILL_DEBIT', $model->getDirectdebit());
        Configuration::updateValue('PIGMBH_PAYMILL_CREDITCARD', $model->getCreditcard());
        Configuration::updateValue('PIGMBH_PAYMILL_PUBLICKEY', $model->getPublicKey());
        Configuration::updateValue('PIGMBH_PAYMILL_PRIVATEKEY', $model->getPrivateKey());
        Configuration::updateValue('PIGMBH_PAYMILL_DEBIT_DAYS', $model->getDebitDays());
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', $model->getDebug());
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', $model->getLogging());
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', $model->getFastcheckout());
        Configuration::updateValue('PIGMBH_PAYMILL_ACCEPTED_BRANDS', Tools::jsonEncode($model->getAccpetedCreditCards()));
    }

    /**
     * Initiate the Pluginconfiguration
     */
    public function setDefaultConfiguration()
    {
        Configuration::updateValue('PIGMBH_PAYMILL_DEBIT', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_CREDITCARD', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_PUBLICKEY', '');
        Configuration::updateValue('PIGMBH_PAYMILL_PRIVATEKEY', '');
        Configuration::updateValue('PIGMBH_PAYMILL_DEBIT_DAYS', '7');
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', 'OFF');
        Configuration::updateValue(
            'PIGMBH_PAYMILL_ACCEPTED_BRANDS',
            Tools::jsonEncode(
                array(
                    'visa' => false,
                    'mastercard' => false,
                    'amex' => false,
                    'carta-si' => false,
                    'carte-bleue' => false,
                    'diners-club' => false,
                    'jcb' => false,
                    'maestro' => false,
                    'china-unionpay' => false,
                    'discover' => false,
                    'dankort' => false
                )
            )
        );

        return true; //needs to return true for installation
    }

}
