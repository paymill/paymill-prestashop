<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{$public_key|escape:'UTF-8'}';
    var PAYMILL_IMAGE = '{$modul_base|escape:'UTF-8'}/img';
    var prefilled = new Array();
    var submitted = false;
    var acceptedBrands = {$acceptedBrands|escape:'UTF-8'};
</script>
<script type="text/javascript" src="https://bridge.paymill.com/"></script>
<script type="text/javascript" src="{$modul_base|escape:'UTF-8'}/js/Iban.js"></script>
<script type="text/javascript" src="{$modul_base|escape:'UTF-8'}/js/BrandDetection.js"></script>
<script type="text/javascript">
    function isSepa()
    {
        return !isNumber($('#paymill_iban').val().substr(0, 2));
    }

    function isNumber(n)
    {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }

    function validate()
    {
        debug("Paymill handler triggered");
        var result = true;
        var errorMessage;
        var field = new Array();
        $("#paymill-error").text('');
        $(".field-error").removeClass('field-error').animate(300);
        {if $payment == 'creditcard'}
        if (paymill.cardType($('#paymill-card-number').val()).toLowerCase() === 'maestro' && (!$('#paymill-card-cvc').val() || $('#paymill-card-cvc').val() === "000")) {
            $('#paymill-card-cvc').val('000');
        } else if (!paymill.validateCvc($('#paymill-card-cvc').val())) {
            errorMessage = "{l s='Please enter your CVC-code(back of card).' mod='pigmbhpaymill'}";
            $("#paymill-error").append($("<div/>").html(errorMessage));
            field.push($('#paymill-card-cvc'));
            result = false;
        }

        if (!paymill.validateHolder($('#paymill-card-holder').val())) {
            errorMessage = "{l s='Please enter the creditcardholders name.' mod='pigmbhpaymill'}";
            $("#paymill-error").append($("<div/>").html(errorMessage));
            field.push($('#paymill-card-holder'));
            result = false;
        }

        if (!paymill.validateExpiry($('#paymill-card-expirydate').val().split('/')[0], $('#paymill-card-expirydate').val().split('/')[1])) {
            errorMessage = "{l s='Please enter a valid date.' mod='pigmbhpaymill'}";
            $("#paymill-error").append($("<div/>").html(errorMessage));
            field.push($('#paymill-card-expirydate'));
            result = false;
        }

        if (!paymill.validateCardNumber($('#paymill-card-number').val())) {
            errorMessage = "{l s='Please enter your creditcardnumber.' mod='pigmbhpaymill'}";
            $("#paymill-error").append($("<div/>").html(errorMessage));
            field.push($('#paymill-card-number'));
            result = false;
        }

        {elseif $payment == 'debit'}
        if (!paymill.validateHolder($('#paymill_accountholder').val())) {
            errorMessage = "{l s='Please enter the accountholder' mod='pigmbhpaymill'}";
            $("#paymill-error").append($("<div/>").html(errorMessage));
            field.push($('#paymill_accountholder'));
            result = false;
        }

        if (!isSepa()) {
            if (!paymill.validateAccountNumber($('#paymill_iban').val())) {
                errorMessage = "{l s='Please enter your accountnumber.' mod='pigmbhpaymill'}";
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill_iban'));
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_bic').val())) {
                errorMessage = "{l s='Please enter your bankcode.' mod='pigmbhpaymill'}";
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill_bic'));
                result = false;
            }
        } else {
            var iban = new Iban();
            if (!iban.validate($('#paymill_iban').val())) {
                errorMessage = "{l s='Please enter your iban.' mod='pigmbhpaymill'}";
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill_iban'));
                result = false;
            }

            if ($('#paymill_bic').val() === "") {
                errorMessage = "{l s='Please enter your bic.' mod='pigmbhpaymill'}";
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill_bic'));
                result = false;
            }
        }

        {/if}
        if (!result) {
            for (var i = 0; i < field.length; i++) {
                field[i].addClass('field-error');
            }

            $("#paymill-error").show(500);
            $("#submitButton").removeAttr('disabled');
        } else {
            $("#paymill-error").hide(800);
            debug("Validations successful");
        }

        return result;
    }


    function debug(message)
    {
    {if $paymill_debugging == 'true'}
        {if $payment == 'creditcard'}
        console.log('[PaymillCC] ' + message);
        {elseif $payment == 'debit'}
        console.log('[PaymillELV] ' + message);
        {/if}
    {/if}
    }

    $(document).ready(function() {
        prefilled = getFormData(prefilled, true);
        $("#paymill_form").submit(function(event) {
            if (!submitted) {
                event.preventDefault();
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
                                amount_int: {$total|escape:'intval'},
                                currency: '{$currency_iso|escape:'UTF-8'}'
                            }, PaymillResponseHandler);
                            {elseif $payment == 'debit'}
                            if (!isSepa()) {
                                paymill.createToken({
                                    number: $('#paymill_iban').val(),
                                    bank: $('#paymill_bic').val(),
                                    accountholder: $('#paymill_accountholder').val()
                                }, PaymillResponseHandler);
                            } else {
                                paymill.createToken({
                                    iban: $('#paymill_iban').val(),
                                    bic: $('#paymill_bic').val(),
                                    accountholder: $('#paymill_accountholder').val()
                                }, PaymillResponseHandler);
                            }
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

            var allDisabled = true;
            for (possibleAcceptableBrand in acceptedBrands) {
                if (acceptedBrands[possibleAcceptableBrand]) {
                    allDisabled = false;
                }
            }

            if ((brand !== 'unknown' && acceptedBrands[brand]) || allDisabled) {
                $('#paymill-card-number').addClass("paymill-card-number-" + brand);
                if (!detector.validate(cardnumber)) {
                    $('#paymill-card-number').addClass("paymill-card-number-grayscale");
                }
            }
        });

        $('#paymill-card-expirydate').keyup(function() {
            var expiryDate = $("#paymill-card-expirydate").val();
            if (expiryDate.match(/^\d\d$/)) {
                expiryDate += "/";
                $("#paymill-card-expirydate").val(expiryDate);
            }
        });

        function getFormData(array, ignoreEmptyValues)
        {
            $('#paymill_form :input:text').each(function() {
                if ($(this).val() === "" && ignoreEmptyValues) {
                    return;
                }
                array.push($(this).val());
            });
            return array;
        }

        function PaymillResponseHandler(error, result)
        {
            debug("Started Paymill response handler");
            if (error) {
                $("#submitButton").removeAttr('disabled');
                debug("API returned error(RAW): " + error.apierror);
                debug("API returned error: " + getErrorMessage(error.apierror));
                alert("API returned error: " + getErrorMessage(error.apierror));
                submitted = false;
                if (error.apierror === 'invalid_public_key' || error.apierror === 'unknown_error') {
                    location.href = 'index.php?controller=order&step=3&paymillerror=1&errorCode=10001';
                }
            } else {
                debug("Received token from Paymill API: " + result.token);
                var form = $("#paymill_form");
                var token = result.token;
                submitted = true;
                form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
                form.submit();
            }
        }

        function getErrorMessage(code)
        {
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
});
</script>