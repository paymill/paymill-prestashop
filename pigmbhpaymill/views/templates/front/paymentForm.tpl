<link rel="stylesheet" type="text/css" href="{$components}/paymill_styles.css" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key}';
    var PAYMILL_IMAGE = '{$components}/images';
    var prefilled = new Array();
    var submitted = false;
</script>
<script type="text/javascript" src="https://bridge.paymill.com/"></script>
<script type="text/javascript" src="{$components}/javascript/Iban.js"></script>
<script type="text/javascript" src="{$components}/javascript/BrandDetection.js"></script>
<script type="text/javascript">
    function validate() {
        debug("Paymill handler triggered");
        var result = true;
        var errorMessage;
        var field = new Array();
        $(".field-error").removeClass('field-error').animate(300);
    {if $payment == 'creditcard'}
        if (paymill.cardType($('#paymill-card-number').val()).toLowerCase() === 'maestro' && (!$('#paymill-card-cvc').val() || $('#paymill-card-cvc').val() === "000")) {
            $('#paymill-card-cvc').val('000');
        } else if (!paymill.validateCvc($('#paymill-card-cvc').val())) {
            errorMessage = '{l s='Please enter your CVC-code(back of card).' mod='pigmbhpaymill'}';
            field.push($('#paymill-card-cvc'));
            result = false;
        }
        if (!paymill.validateHolder($('#paymill-card-holder').val())) {
            errorMessage = '{l s='Please enter the creditcardholders name.' mod='pigmbhpaymill'}';
            field.push($('#paymill-card-holder'));
            result = false;
        }
        if (!paymill.validateExpiry($('#paymill-card-expirydate').val().split('/')[0], $('#paymill-card-expirydate').val().split('/')[1])) {
            errorMessage = '{l s='Please enter a valid date.' mod='pigmbhpaymill'}';
            field.push($('#paymill-card-expirydate'));
            result = false;
        }
        if (!paymill.validateCardNumber($('#paymill-card-number').val())) {
            errorMessage = '{l s='Please enter your creditcardnumber.' mod='pigmbhpaymill'}';
            field.push($('#paymill-card-number'));
            result = false;
        }
    {elseif $payment == 'debit'}
        if (!paymill.validateHolder($('#paymill_accountholder').val())) {
            errorMessage = '{l s='Please enter the accountholder' mod='pigmbhpaymill'}';
            field.push($('#paymill_accountholder'));
            result = false;
        }
        {if !$paymill_sepa}
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
            errorMessage = '{l s='Please enter your accountnumber.' mod='pigmbhpaymill'}';
            field.push($('#paymill_accountnumber'));
            result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
            errorMessage = '{l s='Please enter your bankcode.' mod='pigmbhpaymill'}';
            field.push($('#paymill_banknumber'));
            result = false;
        }
        {else}
        var iban = new Iban();
        if (!iban.validate($('#paymill_iban').val())) {
            errorMessage = '{l s='Please enter your iban.' mod='pigmbhpaymill'}';
            field.push($('#paymill_iban'));
            result = false;
        }
        if ($('#paymill_bic').val() === "") {
            errorMessage = '{l s='Please enter your bic.' mod='pigmbhpaymill'}';
            field.push($('#paymill_bic'));
            result = false;
        }
        {/if}
    {/if}
        if (!result) {
            for (var i = 0; i < field.length; i++) {
                field[i].addClass('field-error');
            }
            $("#paymill-error").html(errorMessage);
            $("#paymill-error").show(500);
            $("#submitButton").removeAttr('disabled');
        } else {
            $("#paymill-error").hide(800);
            debug("Validations successful");
        }

        return result;
    }
    $(document).ready(function() {
        prefilled = getFormData(prefilled, true);
        $("#paymill_form").submit(function(event) {
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
                                number: $('#paymill-card-number').val(),
                                cardholder: $('#paymill-account-holder').val(),
                                exp_month: $('#paymill-card-expirydate').val().split('/')[0],
                                exp_year: $('#paymill-card-expirydate').val().split('/')[1],
                                cvc: $('#paymill-card-cvc').val(),
                                amount_int: {$total},
                                currency: '{$currency_iso}'
                            }, PaymillResponseHandler);
    {elseif $payment == 'debit'}
        {if !$paymill_sepa}
                            paymill.createToken({
                                number: $('#paymill_accountnumber').val(),
                                bank: $('#paymill_banknumber').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
        {else}
                            paymill.createToken({
                                iban: $('#paymill_iban').val(),
                                bic: $('#paymill_bic').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
        {/if}
    {/if}
                        } catch (e) {
                            alert("Ein Fehler ist aufgetreten: " + e);
                        }
                    }
                }
            }
            return submitted;
        });

        $('#paymill-card-number').keyup(function() {
            $("#paymill-card-number")[0].className = $("#paymill-card-number")[0].className.replace(/paymill-card-number-.*/g, '');
            var cardnumber = $('#paymill-card-number').val();
            var detector = new BrandDetection();
            var brand = detector.detect(cardnumber);
            if (brand !== 'unknown') {
                $('#paymill-card-number').addClass("paymill-card-number-" + brand);
                if (!detector.validate(cardnumber)) {
                    $('#paymill-card-number').addClass("paymill-card-number-grayscale");
                }
            }
        });

        $('#paymill-card-expirydate').keyup(function() {
            var expiryDate = $("#paymill-card-expirydate").val();
            if (expiryDate.match(/^.{2}$/)) {
                expiryDate += "/";
                $("#paymill-card-expirydate").val(expiryDate);
            }
        });

    {if $paymill_sepa}
        $('#paymill_iban').keyup(function() {
            var iban = $('#paymill_iban').val();
            if (!iban.match(/^DE/)) {
                var newVal = "DE";
                if (iban.match(/^.{2}(.*)/)) {
                    newVal += iban.match(/^.{2}(.*)/)[1];
                }
                $('#paymill_iban').val(newVal);
            }
        });
        $('#paymill_iban').trigger('keyup');
    {/if}
    });

    function getFormData(array, ignoreEmptyValues) {
        $('#paymill_form :input:text').each(function() {
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
            debug("API returned error(RAW): " + error.apierror);
            debug("API returned error: " + getErrorMessage(error.apierror));
            alert("API returned error: " + getErrorMessage(error.apierror));
            submitted = false;
        } else {
            debug("Received token from Paymill API: " + result.token);
            var form = $("#paymill_form");
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
    function getErrorMessage(code) {
        var errormessage = '{l s='Unknown Error' mod='pigmbhpaymill'}';
        switch (code) {
            case "internal_server_error":
                errormessage = '{l s='Communication with PSP failed' mod='pigmbhpaymill'}';
                break;
            case "invalid_public_key":
                errormessage = '{l s='Public Key is invalid' mod='pigmbhpaymill'}';
                break;
            case "invalid_payment_data":
                errormessage = '{l s='Payment mode, card type, currency or country not accepted.' mod='pigmbhpaymill'}';
                break;
            case "3ds_cancelled":
                errormessage = '{l s='3-D Secure process has been aborted' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_number":
                errormessage = '{l s='Invalid or missing card number' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_exp_year":
                errormessage = '{l s='Invalid or missing expiry year' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_exp_month":
                errormessage = '{l s='Invalid or missing expiry month' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_exp":
                errormessage = '{l s='Card no longer (or not yet) valid' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_cvc":
                errormessage = '{l s='Invalid CVC' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_card_holder":
                errormessage = '{l s='Invalid card holder' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_amount_int":
            case "field_invalid_amount":
                errormessage = '{l s='Invalid or missing amount for 3-D Secure' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_currency":
                errormessage = '{l s='Invalid or missing currency for 3-D Secure' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_account_number":
                errormessage = '{l s='Invalid or missing account number' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_account_holder":
                errormessage = '{l s='Invalid or missing account holder' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_bank_code":
                errormessage = '{l s='Invalid or missing bank code' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_iban":
                errormessage = '{l s='Invalid or missing IBAN' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_bic":
                errormessage = '{l s='Invalid or missing BIC' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_country":
                errormessage = '{l s='Missing or not supported country' mod='pigmbhpaymill'}';
                break;
            case "field_invalid_bank_data":
                errormessage = '{l s='Bank data does not match' mod='pigmbhpaymill'}';
                break;
        }
        return $("<div/>").html(errormessage).text();
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

    <form id='paymill_form' action="{$link->getModuleLink('pigmbhpaymill', 'validation', [], true)}" method="post">
        <div class="debit">
            <input type="hidden" name="payment" value="{$payment}">
            <div id="paymill-error" class="error center" style="display:none;"></div>
            {if $payment == "creditcard"}
                <fieldset>
                    <label for="paymill-card-number" class="field-left">{l s='Creditcard-number' mod='pigmbhpaymill'}*</label>
                    <input id="paymill-card-number" type="text" class="field-left" value="{if $prefilledFormData.last4}****************{$prefilledFormData.last4}{/if}" />
                    <label for="paymill-card-expirydate" class="field-right">{l s='Valid until' mod='pigmbhpaymill'}*</label>
                    <input id="paymill-card-expirydate" type="text" class="field-right" value="{if $prefilledFormData.expire_date}{$prefilledFormData.expire_date}{else}MM/YYYY{/if}">
                </fieldset>
                <fieldset>
                    <label for="paymill-card-holder" class="field-left">{l s='Cardholder' mod='pigmbhpaymill'}*</label>
                    <input id="paymill-card-holder" type="text" class="field-left" value="{if $prefilledFormData.card_holder}{$prefilledFormData.card_holder}{else}{$customer}{/if}"/>
                    <label for="paymill-card-cvc" class="field-right">{l s='CVC' mod='pigmbhpaymill'}*<span class="paymill-tooltip" title="{l s='What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard’s CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.' mod='pigmbhpaymill'}">?</span></label>
                    <input id="paymill-card-cvc" type="text" class="field-right" value="{if $prefilledFormData.last4}***{/if}" />
                </fieldset>
            {elseif $payment == "debit"}
                <fieldset>
                    {if !$paymill_sepa}
                        <label for="paymill_accountnumber" class="field-left">{l s='Accountnumber' mod='pigmbhpaymill'}*</label>
                        <input id="paymill_accountnumber" type="text" class="field-left" value="{if $prefilledFormData.account}{$prefilledFormData.account}{/if}" />
                        <label for="paymill_banknumber" class="field-right">{l s='Banknumber' mod='pigmbhpaymill'}*</label>
                        <input id="paymill_banknumber" type="text" class="field-right" value="{if $prefilledFormData.code}{$prefilledFormData.code}{/if}" />
                    {else}
                        <label for="paymill_iban" class="field-left">IBAN*</label>
                        <input id="paymill_iban" type="text" class="field-left" value="{if $prefilledFormData.iban}{$prefilledFormData.iban}{/if}" />
                        <label for="paymill_bic" class="field-right">BIC*</label>
                        <input id="paymill_bic" type="text" class="field-right" value="{if $prefilledFormData.bic}{$prefilledFormData.bic}{/if}" />
                    {/if}
                </fieldset>
                <fieldset>
                    <label for="paymill_accountholder" class="field-full">{l s='Accountholder' mod='pigmbhpaymill'}*</label>
                    <input id="paymill_accountholder" type="text" class="field-full" value="{if $prefilledFormData.holder}{$prefilledFormData.holder}{else}{$customer}{/if}"/>
                </fieldset>
            {/if}
            <p class="description">
                {l s='The following Amount will be charged' mod='pigmbhpaymill'}: <b>{displayPrice price=$displayTotal}</b><br>
                {l s='Fields marked with a * are required' mod='pigmbhpaymill'}
            </p>
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