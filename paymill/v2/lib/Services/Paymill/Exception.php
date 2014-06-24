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
 * Services_Paymill_Exception class
 */
class Services_Paymill_Exception extends Exception
{
  /**
   * Constructor for exception object
   *
   * @return void
   */
  public function __construct($message, $code)
  {
        parent::__construct($message, $code);
  }
}