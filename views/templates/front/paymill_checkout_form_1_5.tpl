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
<link rel="stylesheet" type="text/css" href="{$modul_base|escape:'UTF-8'}/css/paymill_styles.css" />
<link rel="stylesheet" type="text/css" href="{$modul_base|escape:'UTF-8'}/css/paymill_checkout_1_5.css" />
<div class="row">
    <div class="col-md-12">
        {capture name=path}{l s='Paymill' mod='pigmbhpaymill'}{/capture}
        {include file="$tpl_dir./breadcrumb.tpl"}

        <h2>{l s='Order summary' mod='pigmbhpaymill'}</h2>

        {assign var='current_step' value='payment'}
        {include file="$tpl_dir./order-steps.tpl"}
{if $nbProducts <= 0}
        <p class="warning">{l s='Your cart is empty.' mod='pigmbhpaymill'}</p>
{else}
    </div>
</div>

<form id='paymill_form' action="{$link->getModuleLink('pigmbhpaymill', 'validation', [], true)|escape:'UTF-8'}" method="post">
    <div class="debit">
        <input type="hidden" name="payment" value="{$payment|escape:'UTF-8'}">
        <div id="paymill-error" class="error center" style="display:none;"></div>
        {if $payment == "creditcard"}
            {if $acceptedBrandsDecoded.visa}<img src="{$modul_base|escape:'UTF-8'}img/32x20_visa.png" alt="visa">{/if}
            {if $acceptedBrandsDecoded.mastercard}<img src="{$modul_base|escape:'UTF-8'}img/32x20_mastercard.png" alt="mastercard"> {/if}
            {if $acceptedBrandsDecoded.amex}<img src="{$modul_base|escape:'UTF-8'}img/32x20_amex.png" alt="amex"> {/if}
            {if $acceptedBrandsDecoded.cartasi}<img src="{$modul_base|escape:'UTF-8'}img/32x20_carta-si.png" alt="carta-si"> {/if}
            {if $acceptedBrandsDecoded.cartebleue}<img src="{$modul_base|escape:'UTF-8'}img/32x20_carte-bleue.png" alt="carte-bleue"> {/if}
            {if $acceptedBrandsDecoded.dinersclub}<img src="{$modul_base|escape:'UTF-8'}img/32x20_dinersclub.png" alt="maestro"> {/if}
            {if $acceptedBrandsDecoded.chinaunionpay}<img src="{$modul_base|escape:'UTF-8'}img/32x20_unionpay.png" alt="china-unionpay"> {/if}
            {if $acceptedBrandsDecoded.discover}<img src="{$modul_base|escape:'UTF-8'}img/32x20_discover.png" alt="discover"> {/if}
            {if $acceptedBrandsDecoded.dankort}<img src="{$modul_base|escape:'UTF-8'}img/32x20_dankort.png" alt="dankort"> {/if}
            {if $acceptedBrandsDecoded.jcb}<img src="{$modul_base|escape:'UTF-8'}img/32x20_jcb.png" alt="jcb"> {/if}
            {if $acceptedBrandsDecoded.maestro}<img src="{$modul_base|escape:'UTF-8'}img/32x20_maestro.png" alt="maestro"> {/if}
        <fieldset>
            <label for="paymill-card-number" class="field-left">{l s='Creditcard-number' mod='pigmbhpaymill'}*</label>
            <input id="paymill-card-number" type="text" class="field-left" value="{if $prefilledFormData.last4}****************{$prefilledFormData.last4}{/if}" />
            <label for="paymill-card-expirydate" class="field-right">{l s='Valid until' mod='pigmbhpaymill'}*</label>
            <input id="paymill-card-expirydate" type="text" class="field-right" value="{if $prefilledFormData.expire_date}{$prefilledFormData.expire_date|escape:'UTF-8'}{else}MM/YYYY{/if}">
        </fieldset>
        <fieldset>
            <label for="paymill-card-holder" class="field-left">{l s='Cardholder' mod='pigmbhpaymill'}*</label>
            <input id="paymill-card-holder" type="text" class="field-left" value="{if $prefilledFormData.card_holder}{$prefilledFormData.card_holder|escape:'UTF-8'}{else}{$customer|escape:'UTF-8'}{/if}"/>
            <label for="paymill-card-cvc" class="field-right">{l s='CVC' mod='pigmbhpaymill'}*<span class="paymill-tooltip" title="{l s='What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard’s CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.' mod='pigmbhpaymill'}">?</span></label>
            <input id="paymill-card-cvc" type="text" class="field-right" value="{if $prefilledFormData.last4}***{/if}" />
        </fieldset>
        {elseif $payment == "debit"}
        <fieldset>
            <label for="paymill_iban" class="field-left">IBAN* / {l s='Accountnumber' mod='pigmbhpaymill'}*</label>
            <input id="paymill_iban" type="text" class="field-left" value="{if $prefilledFormData.iban}{$prefilledFormData.iban|escape:'UTF-8'}{else}{if $prefilledFormData.account|escape:'UTF-8'}{$prefilledFormData.account}{/if}{/if}" />
            <label for="paymill_bic" class="field-right">BIC* / {l s='Banknumber' mod='pigmbhpaymill'}*</label>
            <input id="paymill_bic" type="text" class="field-right" value="{if $prefilledFormData.bic}{$prefilledFormData.bic|escape:'UTF-8'}{else}{if $prefilledFormData.code|escape:'UTF-8'}{$prefilledFormData.code}{/if}{/if}" />
        </fieldset>
        <fieldset>
            <label for="paymill_accountholder" class="field-full">{l s='Accountholder' mod='pigmbhpaymill'}*</label>
            <input id="paymill_accountholder" type="text" class="field-full" value="{if $prefilledFormData.holder}{$prefilledFormData.holder|escape:'UTF-8'}{else}{$customer|escape:'UTF-8'}{/if}"/>
        </fieldset>
        {/if}
        <p class="description">
            {l s='The following Amount will be charged' mod='pigmbhpaymill'}: <b>{displayPrice price=$displayTotal}</b><br>
            {l s='Fields marked with a * are required' mod='pigmbhpaymill'}
        </p>
        <p class="cart_navigation paymill_cart_navi">
            {if $opc}
                <a href="{$link->getPageLink('order', true)|escape:'UTF-8'}" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            {/if}
            {if !$opc}
                <a href="{$link->getPageLink('order', true)|escape:'UTF-8'}?step=3" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            {/if}
            <input type="submit" id='submitButton' value="{l s='Order' mod='pigmbhpaymill'}" class="exclusive_large" style="float: right;" />
        </p>
    </div>
</form>
{/if}