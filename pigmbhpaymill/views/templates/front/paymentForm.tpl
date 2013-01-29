<link rel="stylesheet" type="text/css" href="{$components}paymill_styles.css" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$publickey}';
</script>
<script type="text/javascript" src="{$bridgeurl}"></script>
<script type="text/javascript">
    function validate() {
        var errors = $("#errors");
        errors.parent().hide();
        errors.html("");
        var result = true;
        {if $payment == "creditcard"}
        if (!paymill.validateCardNumber($('#card-number').val())) {
            errors.append("<p>Bitte geben Sie eine gültige Kartennummer ein</p>");
            result = false;
        }
        if (!paymill. validateCvc($('#card-cvc').val())) {
            errors.append("<p>Bitte geben sie einen gültigen Sicherheitscode ein (Rückseite der Karte).</p>");
            result = false;
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
            errors.append("<p>Das Ablaufdatum der Karte ist ungültig.</p>");
            result = false;
        }
        {elseif $payment == "debit"}
        if (!$('#paymill_accountholder').val()) {
          errors.append("<p>Bitte geben Sie den Kontoinhaber an.</p>");
          result = false;
        }
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
          errors.append("<p>Bitte geben Sie eine g&uuml;ltige Kontonummer ein.</p>");
          result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
          errors.append("<p>Bitte geben Sie eine g&uuml;ltige BLZ ein.</p>");
          result = false;
        }
        {/if}
        if (!result) {
            errors.parent().show();
        }
    return result;
}
$(document).ready(function() {
    $("#submitButton").click(function(event) {
        if (validate()) {
            try {
                {if $payment == "creditcard"}
                paymill.createToken({
                    number: $('#card-number').val(),
                    cardholder: "Test",
                    exp_month: $('#card-expiry-month').val(),
                    exp_year: $('#card-expiry-year').val(),
                    cvc: $('#card-cvc').val(),
                    amount_int: {$total} * 100,
                    currency: '{$currency_iso}'
                }, PaymillResponseHandler);
                {elseif $payment == "debit"}
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
    if (error) {
        alert(error.apierror);
    } else {
        var form = $("#submitForm");
        var token = result.token;
        form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
        form.submit();
    }
}
</script>

{capture name=path}{l s='Paymill' mod='pigmbhpaymill'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Bestell&uuml;bersicht' mod='pigmbhpaymill'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Ihr Warenkorb ist leer.' mod='pigmbhpaymill'}</p>
{else}

    <h3>{l s='Paymill Zahlung' mod='pigmbhpaymill'}</h3>
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
                <label>Kreditkarten-nummer *</label>
                <input id="card-number" type="text" size="14" class="text" />
            </p>
            <p class="none">
                <label>CVC*</label>
                <input id="card-cvc" type="text" size="4" class="text" />
            </p>
            <p class="none">
                <label>Gültig bis (MM/YYYY) *</label>
                <input id="card-expiry-year" type="text" style="width: 60px; display: inline-block;" class="text" />
                <input id="card-expiry-month" type="text" style="width: 30px; display: inline-block;" class="text" />
            </p>
            <p class="description">Die mit einem * markierten Felder sind Pflichtfelder.
            </p>
            {if $paymillShowLabel == 'true'}
                <p><div class="paymill_powered"><div class="paymill_credits">Sichere Kreditkartenzahlung powered by <a href="http://www.paymill.de" target="_blank">Paymill</a></div></div></p>
            {/if}
            {elseif $payment == "debit"}
            <input type="hidden" name="payment" value="debit">
            <p class="none">
                <label>Kontoinhaber *</label>
                <input id="paymill_accountholder" type="text" size="15" class="text" />
            </p>
            <p class="none">
                <label>Kontonummer *</label>
                <input id="paymill_accountnumber" type="text" size="15" class="text" />
            </p>
            <p class="none">
                <label>Bankleitzahl *</label>
                <input id="paymill_banknumber" type="text" size="15" class="text" />
            </p>
            <p class="description">Die mit einem * markierten Felder sind Pflichtfelder.
            </p>
            {if $paymillShowLabel == 'true'}
                <p><div class="paymill_powered"><div class="paymill_credits">Sichere ELV-Zahlung <br>powered by <a href="http://www.paymill.de" target="_blank">Paymill</a></div></div></p>
            {/if}
            {/if}

        </div>
        <p class="cart_navigation">
            <a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='Zahlartenauswahl' mod='pigmbhpaymill'}</a>
            <input type="button" id='submitButton' value="{l s='Bestellen' mod='pigmbhpaymill'}" class="exclusive_large" />
        </p>
    </form>
{/if}