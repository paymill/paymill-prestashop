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
                    'PIGMBH_PAYMILL_LABEL',
                    'PIGMBH_PAYMILL_DEBIT',
                    'PIGMBH_PAYMILL_CREDITCARD',
                    'PIGMBH_PAYMILL_FASTCHECKOUT',
                    'PIGMBH_PAYMILL_DIFFERENTAMOUNT'
                )
        );
        $configModel->setPublicKey(isset($config['PIGMBH_PAYMILL_PUBLICKEY']) ? $config['PIGMBH_PAYMILL_PUBLICKEY'] : '');
        $configModel->setPrivateKey(isset($config['PIGMBH_PAYMILL_PRIVATEKEY']) ? $config['PIGMBH_PAYMILL_PRIVATEKEY'] : '');
        $configModel->setDebug(isset($config['PIGMBH_PAYMILL_DEBUG']) ? $config['PIGMBH_PAYMILL_DEBUG'] : false);
        $configModel->setLogging(isset($config['PIGMBH_PAYMILL_LOGGING']) ? $config['PIGMBH_PAYMILL_LOGGING'] : false);
        $configModel->setLabel(isset($config['PIGMBH_PAYMILL_LABEL']) ? $config['PIGMBH_PAYMILL_LABEL'] : false);
        $configModel->setDirectdebit(isset($config['PIGMBH_PAYMILL_DEBIT']) ? $config['PIGMBH_PAYMILL_DEBIT'] : false);
        $configModel->setCreditcard(isset($config['PIGMBH_PAYMILL_CREDITCARD']) ? $config['PIGMBH_PAYMILL_CREDITCARD'] : false);
        $configModel->setFastcheckout(isset($config['PIGMBH_PAYMILL_FASTCHECKOUT']) ? $config['PIGMBH_PAYMILL_FASTCHECKOUT'] : false);
        $configModel->setDifferentAmount(isset($config['PIGMBH_PAYMILL_DIFFERENTAMOUNT']) ? $config['PIGMBH_PAYMILL_DIFFERENTAMOUNT'] : "0");
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
        Configuration::updateValue('PIGMBH_PAYMILL_DIFFERENTAMOUNT', $model->getDifferentAmount());
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', $model->getDebug());
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', $model->getLogging());
        Configuration::updateValue('PIGMBH_PAYMILL_LABEL', $model->getLabel());
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', $model->getFastcheckout());
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
        Configuration::updateValue('PIGMBH_PAYMILL_DIFFERENTAMOUNT', '');
        Configuration::updateValue('PIGMBH_PAYMILL_DEBUG', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_LOGGING', 'ON');
        Configuration::updateValue('PIGMBH_PAYMILL_LABEL', 'OFF');
        Configuration::updateValue('PIGMBH_PAYMILL_FASTCHECKOUT', 'OFF');
        return true; //needs to return true for installation
    }

}
