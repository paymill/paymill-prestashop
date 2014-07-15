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
<form action="{$config.action|escape:'UTF-8'}" method="post">
    <fieldset>
        <legend>{l s='Settings' mod='pigmbhpaymill'}</legend>

        <!-- Payment Config -->
        <div class="margin-form">
            <p class="paymill_config_header">{l s='config_payments' mod='pigmbhpaymill'}</p>
        </div>
        <div class="clear"></div>

        <label>{l s='Activate creditcard-payment' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="checkbox" name="creditcard" {$config.creditcard|escape:'UTF-8'}/>
        </div>
        <div class="clear"></div>

        <label>{l s='Activate debit-payment' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="checkbox" name="debit" {$config.debit|escape:'UTF-8'} />
        </div>
        <div class="clear"></div>

        <!-- Main Config -->
        <div class="margin-form">
            <p class="paymill_config_header">{l s='config_main' mod='pigmbhpaymill'}</p>
        </div>
        <div class="clear"></div>

        <label>{l s='Activate debugging' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="checkbox" name="debug" {$config.debug|escape:'UTF-8'} />
        </div>
        <div class="clear"></div>

        <label>{l s='Activate logging' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="checkbox" name="logging" {$config.logging|escape:'UTF-8'} />
        </div>
        <div class="clear"></div>

        <label>{l s='Activate fastCheckout' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="checkbox" name="fastcheckout" {$config.fastcheckout|escape:'UTF-8'} />
        </div>
        <div class="clear"></div>

        <label>{l s='Private Key' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="text" class="paymill_config_text" name="privatekey" value="{$config.privatekey|escape:'UTF-8'}" />
        </div>
        <div class="clear"></div>

        <label>{l s='Public Key' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="text" class="paymill_config_text" name="publickey" value="{$config.publickey|escape:'UTF-8'}" />
        </div>
        <div class="clear"></div>

        <label>{l s='Days until the debit' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <input type="text" class="paymill_config_text" name="debit_days" value="{$config.debit_days|escape:'UTF-8'}" />
        </div>
        <div class="clear"></div>

        <label>{l s='Accepted CreditCard Brands' mod='pigmbhpaymill'}</label>
        <div class="margin-form">
            <select multiple name="accepted_brands[]" class="paymill_config_select">
                {foreach from=$config.accepted_brands item=selected key=brand}
                    <option value="{$brand|escape:'UTF-8'}" {if $selected}selected{/if}>{$brand|escape:'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
        <div class="clear"></div>

        <div class="margin-form">
            <input class="button" name="btnSubmit" value="{l s='Save' mod='pigmbhpaymill'}" type="submit" />
        </div>
    </fieldset>
</form>


