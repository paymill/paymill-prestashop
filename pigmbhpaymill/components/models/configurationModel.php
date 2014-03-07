<?php

/**
 * configuration
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class configurationModel
{

    /**
     * @var string
     */
    private $_privateKey;

    /**
     * @var string
     */
    private $_publicKey;

    /**
     * @var boolean
     */
    private $_debug;

    /**
     * @var boolean
     */
    private $_logging;

    /**
     * @var boolean
     */
    private $_fastcheckout;

    /**
     * @var boolean
     */
    private $_creditcard;

    /**
     * @var boolean
     */
    private $_directdebit;

    /**
     * @var boolean
     */
    private $_sepa;

    /**
     * @return boolean
     */
    public function getSepa()
    {
        return $this->_sepa;
    }

    /**
     * @param boolean $sepa
     */
    public function setSepa($sepa)
    {
        $this->_sepa = $sepa;
    }


    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->_privateKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey($privateKey)
    {
        $this->_privateKey = $privateKey;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->_publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey($publicKey)
    {
        $this->_publicKey = $publicKey;
    }

    /**
     * @return boolean
     */
    public function getDebug()
    {
        return $this->_debug;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    /**
     * @return boolean
     */
    public function getLogging()
    {
        return $this->_logging;
    }

    /**
     * @param boolean $logging
     */
    public function setLogging($logging)
    {
        $this->_logging = $logging;
    }

    /**
     * @return boolean
     */
    public function getFastcheckout()
    {
        return $this->_fastcheckout;
    }

    /**
     * @param boolean $fastcheckout
     */
    public function setFastcheckout($fastcheckout)
    {
        $this->_fastcheckout = $fastcheckout;
    }

    /**
     * @return boolean
     */
    public function getCreditcard()
    {
        return $this->_creditcard;
    }

    /**
     * @param boolean $creditcard
     */
    public function setCreditcard($creditcard)
    {
        $this->_creditcard = $creditcard;
    }

    /**
     * @return boolean
     */
    public function getDirectdebit()
    {
        return $this->_directdebit;
    }

    /**
     * @param boolean $directdebit
     */
    public function setDirectdebit($directdebit)
    {
        $this->_directdebit = $directdebit;
    }

}
