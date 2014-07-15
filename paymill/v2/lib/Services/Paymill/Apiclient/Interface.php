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

interface Services_Paymill_Apiclient_Interface
{
    const HTTP_POST = 'POST';
    const HTTP_GET  = 'GET';
    const HTTP_PUT  = 'PUT';
    const HTTP_DELETE  = 'DELETE';

    /**
     * Perform API and handle exceptions
     *
     * @param $action
     * @param array $params
     * @param string $method
     * @return mixed
     */
    public function request($action, $params = array(), $method = 'POST');

}