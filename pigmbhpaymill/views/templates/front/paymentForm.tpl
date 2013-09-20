<link rel="stylesheet" type="text/css" href="{$components}paymill_styles.css" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key}';
    var PAYMILL_IMAGE = '{$components}/images';
</script>
<script type="text/javascript" src="https://bridge.paymill.com/"></script>
<script type="text/javascript">
    function validate() {
        debug("Paymill handler triggered");
        var errors = $("#errors");
        errors.parent().hide();
        errors.html("");
        var result = true;
    {if $payment == 'creditcard'}
        if (!paymill.validateCardNumber($('#card-number').val())) {
            errors.append("<p>Bitte geben Sie eine gültige Kartennummer ein</p>");
            result = false;
        }
        if (!paymill.validateCvc($('#card-cvc').val())) {
            errors.append("<p>Bitte geben sie einen gültigen Sicherheitscode ein (Rückseite der Karte).</p>");
            result = false;
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
            errors.append("<p>Das Ablaufdatum der Karte ist ungültig.</p>");
            result = false;
        }
    {elseif $payment == 'debit'}
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
        } else {
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
            return false;
        });

        $('#card-number').keyup(function() {
            var brand = paymill.cardType($('#card-number').val());
            brand = brand.toLowerCase();
            $('#card-number').prev("img").remove();
            switch (brand) {
                case 'visa':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_visa.png" >');
                    break;
                case 'mastercard':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_mastercard.png" >');
                    break;
                case 'american express':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_amex.png" >');
                    break;
                case 'jcb':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_jcb.png" >');
                    break;
                case 'maestro':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_maestro.png" >');
                    break;
                case 'diners club':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_dinersclub.png" >');
                    break;
                case 'discover':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_discover.png" >');
                    break;
                case 'unionpay':
                    $('#card-number').after('<img src="' + PAYMILL_IMAGE + '/32x20_unionpay.png" >');
                    break;
                case 'unknown':
                default:
                    $('#card-number').next("img").remove();
                    break;
            }
            $('#paymill_card_icon').children().next("img").css({
                "float":"right"
            });
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
        <div class="error" style="display: none">
            <ul id="errors">
            </ul>
        </div>
        <div class="debit">
            {if $payment == "creditcard"}
                <input type="hidden" name="payment" value="creditcard">
                <p class="none">
                    <label>{l s='Accountholder *' mod='pigmbhpaymill'}</label>
                    <input id="account-holder" type="text" size="14" class="text" value="{$customer}"/>
                </p>
                <p class="none" id="paymill_card_icon">
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
            <a href="{$link->getPageLink('order', true, null, "step=3")}" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            <input type="button" id='submitButton' value="{l s='Order' mod='pigmbhpaymill'}" class="exclusive_large" />
        </p>
    </form>
{/if}
