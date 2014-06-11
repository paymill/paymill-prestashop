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

/** configuration
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
	 * @var array
	 */
	private $_accpetedCreditCards;

	/**
	 * @var string
	 */
	private $_debitDays;

	/**
	 * @return string
	 */
	public function getDebitDays()
	{
		return $this->_debitDays;
	}

	/**
	 * @param string $debitDays
	 */
	public function setDebitDays($debitDays)
	{
		$this->_debitDays = $debitDays;
	}

	/**
	 * @return array
	 */
	public function getAccpetedCreditCards()
	{
		return $this->_accpetedCreditCards;
	}

	/**
	 * @param array $accpetedCreditCards
	 */
	public function setAccpetedCreditCards($accpetedCreditCards)
	{
		$this->_accpetedCreditCards = $accpetedCreditCards;
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
