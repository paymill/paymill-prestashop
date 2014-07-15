{**
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
*}

{include file="$tpl_dir../../modules/pigmbhpaymill/views/templates/front/paymill_checkout_js.tpl"}
{if $use_backward_compatible_checkout}
    {include file="$tpl_dir../../modules/pigmbhpaymill/views/templates/front/paymill_checkout_form_1_5.tpl"}
{else}
    {include file="$tpl_dir../../modules/pigmbhpaymill/views/templates/front/paymill_checkout_form_1_6.tpl"}
{/if}
