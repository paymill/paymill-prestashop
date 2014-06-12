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

{if $creditcard === 'on' && $valid_key}
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'creditcard'])|escape:'UTF-8'}" title="{l s='Paymill Creditcard' mod='pigmbhpaymill'}">
            <img src="{$this_path_ssl|escape:'UTF-8'}/../logo.gif">
            {l s='Paymill Creditcard' mod='pigmbhpaymill'}
        </a>
    </p>
{/if}
{if $debit === 'on' && $valid_key}
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'debit'])|escape:'UTF-8'}" title="{l s='Paymill Directdebit' mod='pigmbhpaymill'}">
            <img src="{$this_path_ssl|escape:'UTF-8'}/../logo.gif">
            {l s='Paymill Directdebit' mod='pigmbhpaymill'}
        </a>
    </p>
{/if}