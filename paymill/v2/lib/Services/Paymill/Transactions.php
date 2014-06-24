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

require_once ('Base.php');

/**
 * Paymill API wrapper for transactions resource
 */
class Services_Paymill_Transactions extends Services_Paymill_Base
{
    /**
     * {@inheritDoc}
     */
    protected $_serviceResource = 'transactions/';

    /**
     * General REST DELETE verb
     * Delete or inactivate/cancel resource item
     *
     * @param string $clientId
     *
     * @return array item deleted
     */
    public function delete($clientId = null)
    {
        throw new Services_Paymill_Exception( __CLASS__ . " does not support " . __METHOD__, "404");
    }
}