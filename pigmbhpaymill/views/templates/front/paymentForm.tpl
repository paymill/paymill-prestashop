<link rel="stylesheet" type="text/css" href="{$components}paymill_styles.css" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key}';
    var PAYMILL_IMAGE = '{$components}/images';
    var prefilled = new Array();
    var submitted = false;
</script>
<script type="text/javascript" src="https://bridge.paymill.com/"></script>
<script type="text/javascript">
    function validate() {
        debug("Paymill handler triggered");
        var result = true;
        $('.error').remove();
    {if $payment == 'creditcard'}
        if ($('#account-holder').val() === "") {
            $('#account-holder').after("<p class='error paymillerror'>{l s='Please enter the creditcardholders name.' mod='pigmbhpaymill'}</p>");
            result = false;
        }
        if (!paymill.validateCardNumber($('#card-number').val())) {
            $('#card-number').after("<p class='error paymillerror'>{l s='Please enter your creditcardnumber.' mod='pigmbhpaymill'}</p>");
            result = false;
        }
        if (paymill.cardType($('#card-number').val()).toLowerCase() === 'maestro' && (!$('#card-cvc').val() || $('#card-cvc').val() === "000")) {
            $('#card-cvc').val('000');
        } else if (!paymill.validateCvc($('#card-cvc').val())) {
            $('#card-cvc').after("<p class='error paymillerror'>{l s='Please enter your CVC-code(back of card).' mod='pigmbhpaymill'}</p>");
            result = false;
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
            $('#card-expiry-year').after("<p class='error paymillerror'>{l s='Please enter a valid date.' mod='pigmbhpaymill'}</p>");
            result = false;
        }
    {elseif $payment == 'debit'}
        if (!$('#paymill_accountholder').val()) {
            $('#paymill_accountholder').after("<p class='error paymillerror'>{l s='Please enter the accountholder' mod='pigmbhpaymill'}</p>");
            result = false;
        }
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
            $('#paymill_accountnumber').after("<p class='error paymillerror'>{l s='Please enter your accountnumber.' mod='pigmbhpaymill'}</p>");
            result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
            $('#paymill_banknumber').after("<p class='error paymillerror'>{l s='Please enter your bankcode.' mod='pigmbhpaymill'}</p>");
            result = false;
        }
    {/if}
        if (!result) {
            $("#submitButton").removeAttr('disabled');
        } else {
            debug("Validations successful");
        }

        return result;
    }
    $(document).ready(function() {
        prefilled = getFormData(prefilled, true);
        $("#submitForm").submit(function(event) {
            if (!submitted) {
                $("#submitButton").attr('disabled', true);
                var formdata = new Array();
                formdata = getFormData(formdata, false);

                if (prefilled.toString() === formdata.toString()) {
                    result = new Object();
                    result.token = 'dummyToken';
                    PaymillResponseHandler(null, result);
                } else {
                    if (validate()) {
                        try {
    {if $payment == 'creditcard'}
                            paymill.createToken({
                                number: $('#card-number').val(),
                                cardholder: $('#account-holder').val(),
                                exp_month: $('#card-expiry-month').val(),
                                exp_year: $('#card-expiry-year').val(),
                                cvc: $('#card-cvc').val(),
                                amount_int: {$total},
                                currency: '{$currency_iso}'
                            }, PaymillResponseHandler);
    {elseif $payment == 'debit'}
                            paymill.createToken({
                                number: $('#paymill_accountnumber').val(),
                                bank: $('#paymill_banknumber').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
    {/if}
                        } catch (e) {
                            alert("Ein Fehler ist aufgetreten: " + e);
                        }
                    }
                }
            }
            return submitted;
        });

        $('#card-number').keyup(function() {
            var brand = paymill.cardType($('#card-number').val());
            brand = brand.toLowerCase();
            $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
            if (brand !== 'unknown') {
                if (brand === 'american express') {
                    brand = 'amex';
                }
                $('#card-number').addClass("paymill-card-number-" + brand);
            }
        });
    });
    function getFormData(array, ignoreEmptyValues) {
        $('#submitForm :input').not(':[type=hidden]').each(function() {
            if ($(this).val() === "" && ignoreEmptyValues) {
                return;
            }
            array.push($(this).val());
        });
        return array;
    }
    function PaymillResponseHandler(error, result) {
        debug("Started Paymill response handler");
        if (error) {
            $("#submitButton").removeAttr('disabled');
            debug("API returned error:" + error.apierror);
            alert("API returned error:" + error.apierror);
            submitted = false;
        } else {
            debug("Received token from Paymill API: " + result.token);
            var form = $("#submitForm");
            var token = result.token;
            submitted = true;
            form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
            form.submit();
        }
    }
    function debug(message) {
    {if $paymill_debugging == 'true'}
        {if $payment == 'creditcard'}
        console.log('[PaymillCC] ' + message);
        {elseif $payment == 'debit'}
        console.log('[PaymillELV] ' + message);
        {/if}
    {/if}
    }

</script>

{capture name=path}{l s='Paymill' mod='pigmbhpaymill'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='pigmbhpaymill'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Your cart is empty.' mod='pigmbhpaymill'}</p>
{else}

    <h3>{l s='Paymill payment' mod='pigmbhpaymill'}</h3>
    <form id='submitForm' action="{$link->getModuleLink('pigmbhpaymill', 'validation', [], true)}" method="post">
        <div class="debit">
            {if $payment == "creditcard"}
                <input type="hidden" name="payment" value="creditcard">
                <p class="none">
                    <label>{l s='Accountholder *' mod='pigmbhpaymill'}</label><br>
                    <input id="account-holder" type="text" size="14" class="text" value="{if $prefilledFormData['card_holder']}{$prefilledFormData['card_holder']}{else}{$customer}{/if}"/>
                </p>
                <p class="none" id="paymill_card_icon">
                    <label>{l s='Creditcard-number *' mod='pigmbhpaymill'}</label><br>
                    <input id="card-number" type="text" size="14" class="text" value="{if $prefilledFormData['last4']}****************{$prefilledFormData['last4']}{/if}" />
                </p>
                <p class="none">
                    <label>{l s='CVC' mod='pigmbhpaymill'}</label><br>
                    <input id="card-cvc" type="text" size="4" class="text" value="{if $prefilledFormData['last4']}***{/if}" />
                    <span class="tooltip" title="{l s='What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard’s CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.' mod='pigmbhpaymill'}">?</span>
                </p>
                <p class="none">
                    <label>{l s='Valid until (MM/YYYY) *' mod='pigmbhpaymill'}</label><br>
                    <select id="card-expiry-month" class="Paymillselect">
                        <option value="1" {if $prefilledFormData['expire_month'] == 1}selected{/if}>{l s='January' mod='pigmbhpaymill'}</option>
                        <option value="2" {if $prefilledFormData['expire_month'] == 2}selected{/if}>{l s='February' mod='pigmbhpaymill'}</option>
                        <option value="3" {if $prefilledFormData['expire_month'] == 3}selected{/if}>{l s='March' mod='pigmbhpaymill'}</option>
                        <option value="4" {if $prefilledFormData['expire_month'] == 4}selected{/if}>{l s='April' mod='pigmbhpaymill'}</option>
                        <option value="5" {if $prefilledFormData['expire_month'] == 5}selected{/if}>{l s='May' mod='pigmbhpaymill'}</option>
                        <option value="6" {if $prefilledFormData['expire_month'] == 6}selected{/if}>{l s='June' mod='pigmbhpaymill'}</option>
                        <option value="7" {if $prefilledFormData['expire_month'] == 7}selected{/if}>{l s='July' mod='pigmbhpaymill'}</option>
                        <option value="8" {if $prefilledFormData['expire_month'] == 8}selected{/if}>{l s='August' mod='pigmbhpaymill'}</option>
                        <option value="9" {if $prefilledFormData['expire_month'] == 9}selected{/if}>{l s='September' mod='pigmbhpaymill'}</option>
                        <option value="10" {if $prefilledFormData['expire_month'] == 10}selected{/if}>{l s='October' mod='pigmbhpaymill'}</option>
                        <option value="11" {if $prefilledFormData['expire_month'] == 11}selected{/if}>{l s='November' mod='pigmbhpaymill'}</option>
                        <option value="12" {if $prefilledFormData['expire_month'] == 12}selected{/if}>{l s='December' mod='pigmbhpaymill'}</option>
                    </select>
                    <select id="card-expiry-year" class="Paymillselect">
                        {foreach from=$paymill_form_year item=year}
                            {if $prefilledFormData['expire_year'] == $year}
                                <option value="{$year}" selected>{$year}</option>
                            {else}
                                <option value="{$year}">{$year}</option>
                            {/if}
                        {/foreach}
                    </select>
                </p>
                <p class="description">
                    {l s='The following Amount will be charged' mod='pigmbhpaymill'}: <b>{displayPrice price=$displayTotal}</b><br>
                    {l s='Fields marked with a * are required' mod='pigmbhpaymill'}
                </p>
                {if $paymill_show_label == 'true'}
                    <p><div class="paymill_powered"><div class="paymill_credits">{l s='Save creditcardpayment powered by' mod='pigmbhpaymill'} <a href="http://www.paymill.de" target="_blank">PAYMILL</a></div></div></p>
                {/if}
            {elseif $payment == "debit"}
                <input type="hidden" name="payment" value="debit">
                <p class="none">
                    <label>{l s='Accountholder *' mod='pigmbhpaymill'}</label><br>
                    <input id="paymill_accountholder" type="text" size="15" class="text" value="{if $prefilledFormData['holder']}{$prefilledFormData['holder']}{else}{$customer}{/if}"/>
                </p>
                <p class="none">
                    <label>{l s='Accountnumber *' mod='pigmbhpaymill'}</label><br>
                    <input id="paymill_accountnumber" type="text" size="15" class="text" value="{if $prefilledFormData['account']}{$prefilledFormData['account']}{/if}" />
                </p>
                <p class="none">
                    <label>{l s='Banknumber *' mod='pigmbhpaymill'}</label><br>
                    <input id="paymill_banknumber" type="text" size="15" class="text" value="{if $prefilledFormData['code']}{$prefilledFormData['code']}{/if}" />
                </p>
                <p class="description">
                    {l s='The following Amount will be charged' mod='pigmbhpaymill'}: <b>{displayPrice price=$displayTotal}</b><br>
                    {l s='Fields marked with a * are required' mod='pigmbhpaymill'}
                </p>
                {if $paymill_show_label == 'true'}
                    <p><div class="paymill_powered"><div class="paymill_credits">{l s='debitpayment powered by' mod='pigmbhpaymill'} <a href="http://www.paymill.de" target="_blank">PAYMILL</a></div></div></p>
                {/if}
            {/if}

        </div>
        <p class="cart_navigation">
            {if $opc}
                <a href="{$link->getPageLink('order', true)}" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            {/if}
            {if !$opc}
                <a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            {/if}
            <input type="submit" id='submitButton' value="{l s='Order' mod='pigmbhpaymill'}" class="exclusive_large" />
        </p>
    </form>
{/if}
