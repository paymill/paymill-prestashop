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

<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key|escape:'html'}';
    var paymillcheckout = new Object();
    paymillcheckout.errormessages = new Object();
    paymillcheckout.errormessages.bridge = {
        unknown: '{l s='Unknown Error' mod='pigmbhpaymill'}',
        internal_server_error: '{l s='Communication with PSP failed' mod='pigmbhpaymill'}',
        invalid_public_key: '{l s='Public Key is invalid' mod='pigmbhpaymill'}',
        invalid_payment_data: '{l s='Payment mode, card type, currency or country not accepted.' mod='pigmbhpaymill'}',
        cancelled3DS: '{l s='3-D Secure process has been aborted' mod='pigmbhpaymill'}',
        invalid_card_number: '{l s='Invalid or missing card number' mod='pigmbhpaymill'}',
        invalid_card_exp_year: '{l s='Invalid or missing expiry year' mod='pigmbhpaymill'}',
        invalid_card_exp_month: '{l s='Invalid or missing expiry month' mod='pigmbhpaymill'}',
        invalid_card_exp: '{l s='Card no longer (or not yet) valid' mod='pigmbhpaymill'}',
        invalid_card_cvc: '{l s='Invalid CVC' mod='pigmbhpaymill'}',
        invalid_card_holder: '{l s='Invalid card holder' mod='pigmbhpaymill'}',
        invalid_amount: '{l s='Invalid or missing amount for 3-D Secure' mod='pigmbhpaymill'}',
        invalid_currency: '{l s='Invalid or missing currency for 3-D Secure' mod='pigmbhpaymill'}',
        invalid_account_number: '{l s='Invalid or missing account number' mod='pigmbhpaymill'}',
        invalid_account_holder: '{l s='Invalid or missing account holder' mod='pigmbhpaymill'}',
        invalid_bank_code: '{l s='Invalid or missing bank code' mod='pigmbhpaymill'}',
        invalid_iban: '{l s='Invalid or missing IBAN' mod='pigmbhpaymill'}',
        invalid_bic: '{l s='Invalid or missing BIC' mod='pigmbhpaymill'}',
        invalid_country: '{l s='Missing or not supported country' mod='pigmbhpaymill'}',
        invalid_bank_data: '{l s='Bank data does not match' mod='pigmbhpaymill'}'
    };
    paymillcheckout.errormessages.validation = {
        invalid_cvc: "{l s='Please enter your CVC-code(back of card).' mod='pigmbhpaymill'}",
        invalid_cardholder: "{l s='Please enter the creditcardholders name.' mod='pigmbhpaymill'}",
        invalid_expirydate: "{l s='Please enter a valid date.' mod='pigmbhpaymill'}",
        invalid_creditcardnumber: "{l s='Please enter your creditcardnumber.' mod='pigmbhpaymill'}",
        invalid_accountholder: "{l s='Please enter the accountholder' mod='pigmbhpaymill'}",
        invalid_accountnumber: "{l s='Please enter your accountnumber.' mod='pigmbhpaymill'}",
        invalid_bankcode: "{l s='Please enter your bankcode.' mod='pigmbhpaymill'}",
        invalid_iban: "{l s='Please enter your iban.' mod='pigmbhpaymill'}",
        invalid_bic: "{l s='Please enter your bic.' mod='pigmbhpaymill'}"
    };
    paymillcheckout.paymentmean = '{$payment|escape:'html':'UTF-8'}';
    paymillcheckout.acceptedBrands = {$acceptedBrands};
    paymillcheckout.debugmode = {$paymill_debugging|escape:'intval'};
    paymillcheckout.amount = {$total|escape:'intval'};
    paymillcheckout.currency = '{$currency_iso|escape:'html':'UTF-8'}';
    paymillcheckout.prefilled = new Array();
    paymillcheckout.submitted = false;
</script>

{if $use_backward_compatible_checkout}
    {include file="$tpl_dir../../modules/pigmbhpaymill/views/templates/front/paymill_checkout_form_1_5.tpl"}
{else}
    {include file="$tpl_dir../../modules/pigmbhpaymill/views/templates/front/paymill_checkout_form_1_6.tpl"}
{/if}
