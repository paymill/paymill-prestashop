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
<div class="row">
    <div class="col-xs-12 col-md-6">
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'creditcard'])|escape:'url':'UTF-8'}" title="{l s='Paymill Creditcard' mod='pigmbhpaymill'}" class="creditcard">
            {l s='Paymill Creditcard' mod='pigmbhpaymill'}
        </a>
    </p>
    </div>
</div>
{/if}
{if $debit === 'on' && $valid_key}
<div class="row">
    <div class="col-xs-12 col-md-6">
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'debit'])|escape:'url':'UTF-8'}" title="{l s='Paymill Directdebit' mod='pigmbhpaymill'}" class="elv">
            {l s='Paymill Directdebit' mod='pigmbhpaymill'}
        </a>
    </p>
    </div>
</div>
{/if}
