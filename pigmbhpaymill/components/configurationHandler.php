<?php

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
        $configModel->setDebug(isset($config['PIGMBH_PAYMILL_DEBUG']) ? $config['PIGMBH_PAYMILL_DEBUG'] : false);
        $configModel->setLogging(isset($config['PIGMBH_PAYMILL_LOGGING']) ? $config['PIGMBH_PAYMILL_LOGGING'] : false);
        $configModel->setDirectdebit(isset($config['PIGMBH_PAYMILL_DEBIT']) ? $config['PIGMBH_PAYMILL_DEBIT'] : false);
        $configModel->setCreditcard(isset($config['PIGMBH_PAYMILL_CREDITCARD']) ? $config['PIGMBH_PAYMILL_CREDITCARD'] : false);
        $configModel->setFastcheckout(isset($config['PIGMBH_PAYMILL_FASTCHECKOUT']) ? $config['PIGMBH_PAYMILL_FASTCHECKOUT'] : false);
        $configModel->setAccpetedCreditCards(isset($config['PIGMBH_PAYMILL_ACCEPTED_BRANDS']) ? json_decode($config['PIGMBH_PAYMILL_ACCEPTED_BRANDS'], true) : false);
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
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', $model->getDebug());
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', $model->getLogging());
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', $model->getFastcheckout());
        Configuration::updateValue('PIGMBH_PAYMILL_ACCEPTED_BRANDS', json_encode($model->getAccpetedCreditCards()));
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
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', 'OFF');
        Configuration::updateValue(
            'PIGMBH_PAYMILL_ACCEPTED_BRANDS',
            json_encode(
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
