<link rel="stylesheet" type="text/css" href="{$components}paymill_styles.css" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key}';
</script>
<script type="text/javascript" src="{$bridge_url}"></script>
<script type="text/javascript">
    function validate() {
        debug("Paymill handler triggered");
        var errors = $("#errors");
        errors.parent().hide();
        errors.html("");
        var result = true;
        {if $payment == 'creditcard'}
        if (!paymill.validateCardNumber($('#card-number').val())) {
            errors.append("<p>Please enter a valid credit card number.</p>");
            result = false;
        }
        if (!paymill. validateCvc($('#card-cvc').val())) {
            errors.append("<p>Please enter a valid credit card Security Code.</p>");
            result = false;
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
            errors.append("<p>Your credit card expiration date is not valid.</p>");
            result = false;
        }
        {elseif $payment == 'debit'}
        if (!$('#paymill_accountholder').val()) {
          errors.append("<p>Please enter the full name of the credit card's owner.</p>");
          result = false;
        }
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
          errors.append("<p>Please enter a valid bank account number.</p>");
          result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
          errors.append("<p>Please enter a valid sort code.</p>");
          result = false;
        }
        {/if}
        if (!result) {
            errors.parent().show();
        }else{
            debug("Validations successful");
        }

    return result;
}
$(document).ready(function() {
    $("#submitButton").click(function(event) {
        if (validate()) {
            try {
                {if $payment == 'creditcard'}
                paymill.createToken({
                    number: $('#card-number').val(),
                    cardholder:  $('#account-holder').val(),
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
        return false;
    });
});
function PaymillResponseHandler(error, result) {
    debug("Started Paymill response handler");
    if (error) {
        debug("API returned error:" + error.apierror);
        alert("API returned error:" + error.apierror);
    } else {
        debug("Received token from Paymill API: " + result.token);
        var form = $("#submitForm");
        var token = result.token;
        form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
        form.submit();
    }
}
function debug(message){
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
        <div class="error" style="display: none">
            <ul id="errors">
            </ul>
        </div>
        <div class="debit">
            {if $payment == "creditcard"}
            <input type="hidden" name="payment" value="creditcard">
            <p>
                <img src="{$components}icon_mastercard.png" />
                <img src="{$components}icon_visa.png" />
            </p>
            <p class="none">
                <label>{l s='Accountholder *' mod='pigmbhpaymill'}</label>
                <input id="account-holder" type="text" size="14" class="text" value="{$customer}"/>
            </p>
            <p class="none">
                <label>{l s='Creditcard-number *' mod='pigmbhpaymill'}</label>
                <input id="card-number" type="text" size="14" class="text" />
            </p>
            <p class="none">
                <label>{l s='CVC *' mod='pigmbhpaymill'}*</label>
                <input id="card-cvc" type="text" size="4" class="text" />
            </p>
            <p class="none">
                <label>{l s='Valid until (MM/YYYY) *' mod='pigmbhpaymill'}</label>
                <input id="card-expiry-year" type="text" style="width: 60px; display: inline-block;" class="text" />
                <input id="card-expiry-month" type="text" style="width: 30px; display: inline-block;" class="text" />
            </p>
            <p class="description">{l s='Fields marked with a * are required' mod='pigmbhpaymill'}
            </p>
            {if $paymill_show_label == 'true'}
                <p><div class="paymill_powered"><div class="paymill_credits">{l s='Save creditcardpayment powered by' mod='pigmbhpaymill'} <a href="http://www.paymill.de" target="_blank">PAYMILL</a></div></div></p>
            {/if}
            {elseif $payment == "debit"}
            <input type="hidden" name="payment" value="debit">
            <p class="none">
                <label>{l s='Accountholder *' mod='pigmbhpaymill'}</label>
                <input id="paymill_accountholder" type="text" size="15" class="text" />
            </p>
            <p class="none">
                <label>{l s='Accountnumber *' mod='pigmbhpaymill'}</label>
                <input id="paymill_accountnumber" type="text" size="15" class="text" />
            </p>
            <p class="none">
                <label>{l s='Banknumber *' mod='pigmbhpaymill'}</label>
                <input id="paymill_banknumber" type="text" size="15" class="text" />
            </p>
            <p class="description">{l s='Fields marked with a * are required' mod='pigmbhpaymill'}
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
    		 <input type="button" id='submitButton' value="{l s='Order' mod='pigmbhpaymill'}" class="exclusive_large" />
        </p>
    </form>
{/if}
