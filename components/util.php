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

/**
 * util
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2014 PayIntelligent GmbH (http://payintelligent.de)
 */
class Util {

	/**
	 * @param integer $id
	 * @return boolean
	 */
	public function isPaymillOrder($id)
	{
		$db = Db::getInstance();
		$order_id = (int)$id;
		$result = $db->executeS('SELECT COUNT(*) AS "count" FROM `'._DB_PREFIX_.'pigmbh_paymill_transactiondata` WHERE `id`='.
				$db->_escape($order_id), true);
		$return_value = false;
		if (is_array($result) && isset($result[0]) && isset($result[0]['count']))
			$return_value = (boolean)$result[0]['count'];
		return $return_value;
	}

}
