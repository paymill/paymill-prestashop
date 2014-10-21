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
class ConfigurationModel {

	/**
	 * @var string
	 */
	private $private_key;

	/**
	 * @var string
	 */
	private $public_key;

	/**
	 * @var boolean
	 */
	private $debug;

	/**
	 * @var boolean
	 */
	private $logging;

	/**
	 * @var boolean
	 */
	private $fastcheckout;

	/**
	 * @var boolean
	 */
	private $creditcard;

	/**
	 * @var boolean
	 */
	private $directdebit;

	/**
	 * @var array
	 */
	private $accpeted_creditcards;

	/**
	 * @var string
	 */
	private $debit_days;

	/**
	 * @var boolean
	 */
	private $capture;

	/**
	 * @return string
	 */
	public function getDebitDays()
	{
		return $this->debit_days;
	}

	/**
	 * @param string $debit_days
	 */
	public function setDebitDays($debit_days)
	{
		$this->debit_days = $debit_days;
	}

	/**
	 * @return array
	 */
	public function getAccpetedCreditCards()
	{
		return $this->accpeted_creditcards;
	}

	/**
	 * @param array $accpeted_creditards
	 */
	public function setAccpetedCreditCards($accpeted_creditards)
	{
		$this->accpeted_creditcards = $accpeted_creditards;
	}

	/**
	 * @return string
	 */
	public function getPrivateKey()
	{
		return $this->private_key;
	}

	/**
	 * @param string $private_key
	 */
	public function setPrivateKey($private_key)
	{
		$this->private_key = $private_key;
	}

	/**
	 * @return string
	 */
	public function getPublicKey()
	{
		return $this->public_key;
	}

	/**
	 * @param string $public_key
	 */
	public function setPublicKey($public_key)
	{
		$this->public_key = $public_key;
	}

	/**
	 * @return boolean
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * @param boolean $debug
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;
	}

	/**
	 * @return boolean
	 */
	public function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @param boolean $logging
	 */
	public function setLogging($logging)
	{
		$this->logging = $logging;
	}

	/**
	 * @return boolean
	 */
	public function getFastcheckout()
	{
		return $this->fastcheckout;
	}

	/**
	 * @param boolean $fastcheckout
	 */
	public function setFastcheckout($fastcheckout)
	{
		$this->fastcheckout = $fastcheckout;
	}

	/**
	 * @return boolean
	 */
	public function getCreditcard()
	{
		return $this->creditcard;
	}

	/**
	 * @param boolean $creditcard
	 */
	public function setCreditcard($creditcard)
	{
		$this->creditcard = $creditcard;
	}

	/**
	 * @return boolean
	 */
	public function getDirectdebit()
	{
		return $this->directdebit;
	}

	/**
	 * @param boolean $directdebit
	 */
	public function setDirectdebit($directdebit)
	{
		$this->directdebit = $directdebit;
	}

	/**
	 * @return boolean
	 */
	public function getCapture()
	{
		return $this->capture;
	}

	/**
	 * @param boolean $capture
	 */
	public function setCapture($capture)
	{
		$this->capture = $capture;
	}

}
