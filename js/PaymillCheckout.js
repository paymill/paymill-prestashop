/**
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
*/
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
        if (paymillcheckout.paymentmean === 'creditcard') {
            if (paymill.cardType($('#paymill-card-number').val()).toLowerCase() === 'maestro' && (!$('#paymill-card-cvc').val() || $('#paymill-card-cvc').val() === "000")) {
                $('#paymill-card-cvc').val('000');
            } else if (!paymill.validateCvc($('#paymill-card-cvc').val())) {
                errorMessage = paymillcheckout.errormessages.validation.invalid_cvc;
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill-card-cvc'));
                result = false;
            }

            if (!paymill.validateHolder($('#paymill-card-holder').val())) {
                errorMessage = paymillcheckout.errormessages.validation.invalid_cardholder;
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill-card-holder'));
                result = false;
            }

            if (!paymill.validateExpiry($('#paymill-card-expirydate').val().split('/')[0], $('#paymill-card-expirydate').val().split('/')[1])) {
                errorMessage = paymillcheckout.errormessages.validation.invalid_expirydate;
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill-card-expirydate'));
                result = false;
            }

            if (!paymill.validateCardNumber($('#paymill-card-number').val())) {
                errorMessage = paymillcheckout.errormessages.validation.invalid_creditcardnumber;
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill-card-number'));
                result = false;
            }
        } else if (paymillcheckout.paymentmean === 'debit') {
            if (!paymill.validateHolder($('#paymill_accountholder').val())) {
                errorMessage = paymillcheckout.errormessages.validation.invalid_accountholder;
                $("#paymill-error").append($("<div/>").html(errorMessage));
                field.push($('#paymill_accountholder'));
                result = false;
            }

            if (!isSepa()) {
                if (!paymill.validateAccountNumber($('#paymill_iban').val())) {
                    errorMessage = paymillcheckout.errormessages.validation.invalid_accountnumber;
                    $("#paymill-error").append($("<div/>").html(errorMessage));
                    field.push($('#paymill_iban'));
                    result = false;
                }

                if (!paymill.validateBankCode($('#paymill_bic').val())) {
                    errorMessage = paymillcheckout.errormessages.validation.invalid_bankcode;
                    $("#paymill-error").append($("<div/>").html(errorMessage));
                    field.push($('#paymill_bic'));
                    result = false;
                }
            } else {
                var iban = new Iban();
                if (!iban.validate($('#paymill_iban').val())) {
                    errorMessage = paymillcheckout.errormessages.validation.invalid_iban;
                    $("#paymill-error").append($("<div/>").html(errorMessage));
                    field.push($('#paymill_iban'));
                    result = false;
                }

                if ($('#paymill_bic').val() === "") {
                    errorMessage = paymillcheckout.errormessages.validation.invalid_bic;
                    $("#paymill-error").append($("<div/>").html(errorMessage));
                    field.push($('#paymill_bic'));
                    result = false;
                }
            }

        }
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
        if (paymillcheckout.debugmode) {
            console.log('[Paymill] ' + message);
        }
    }

    $(document).ready(function() {
        paymillcheckout.prefilled = getFormData(paymillcheckout.prefilled, true);
        $("#paymill_form").submit(function(event) {
            if (!paymillcheckout.submitted) {
                event.preventDefault();
                $("#submitButton").attr('disabled', true);
                var formdata = new Array();
                formdata = getFormData(formdata, false);

                if (paymillcheckout.prefilled.toString() === formdata.toString()) {
                    result = new Object();
                    result.token = 'dummyToken';
                    PaymillResponseHandler(null, result);
                } else {
                    if (validate()) {
                        try {
                            if (paymillcheckout.paymentmean === 'creditcard') {
                                paymill.createToken({
                                    number: $('#paymill-card-number').val(),
                                    cardholder: $('#paymill-account-holder').val(),
                                    exp_month: $('#paymill-card-expirydate').val().split('/')[0],
                                    exp_year: $('#paymill-card-expirydate').val().split('/')[1],
                                    cvc: $('#paymill-card-cvc').val(),
                                    amount_int: paymillcheckout.amount,
                                    currency: paymillcheckout.currency
                                }, PaymillResponseHandler);
                            } else if (paymillcheckout.paymentmean === 'debit') {
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
                            }
                        } catch (e) {
                            alert("Ein Fehler ist aufgetreten: " + e);
                        }
                    }
                }
            }

            return paymillcheckout.submitted;
        });

        $('#paymill-card-number').keyup(function() {
            $("#paymill-card-number")[0].className = $("#paymill-card-number")[0].className.replace(/paymill-card-number-.*/g, '');
            var cardnumber = $('#paymill-card-number').val();
            var detector = new BrandDetection();
            var brand = detector.detect(cardnumber);

            var allDisabled = true;
            for (possibleAcceptableBrand in paymillcheckout.acceptedBrands) {
                if (paymillcheckout.acceptedBrands[possibleAcceptableBrand]) {
                    allDisabled = false;
                }
            }

            if ((brand !== 'unknown' && paymillcheckout.acceptedBrands[brand]) || allDisabled) {
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
                paymillcheckout.submitted = false;
                if (error.apierror === 'invalid_public_key' || error.apierror === 'unknown_error') {
                    location.href = 'index.php?controller=order&step=3&paymillerror=1&errorCode=10001';
                }
            } else {
                debug("Received token from Paymill API: " + result.token);
                var form = $("#paymill_form");
                var token = result.token;
                paymillcheckout.submitted = true;
                form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
                form.submit();
            }
        }

        function getErrorMessage(code)
        {
            var errormessage = paymillcheckout.errormessages.bridge.unknown;
            switch (code) {
                case "internal_server_error":
                    errormessage = paymillcheckout.errormessages.bridge.internal_server_error;
                    break;
                case "invalid_public_key":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_public_key;
                    break;
                case "invalid_payment_data":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_payment_data;
                    break;
                case "3ds_cancelled":
                    errormessage = paymillcheckout.errormessages.bridge.cancelled3DS;
                    break;
                case "field_invalid_card_number":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_number;
                    break;
                case "field_invalid_card_exp_year":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_exp_year;
                    break;
                case "field_invalid_card_exp_month":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_exp_month;
                    break;
                case "field_invalid_card_exp":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_exp;
                    break;
                case "field_invalid_card_cvc":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_cvc;
                    break;
                case "field_invalid_card_holder":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_card_holder;
                    break;
                case "field_invalid_amount_int":
                case "field_invalid_amount":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_amount;
                    break;
                case "field_invalid_currency":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_currency;
                    break;
                case "field_invalid_account_number":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_account_number;
                    break;
                case "field_invalid_account_holder":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_account_holder;
                    break;
                case "field_invalid_bank_code":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_bank_code;
                    break;
                case "field_invalid_iban":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_iban;
                    break;
                case "field_invalid_bic":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_bic;
                    break;
                case "field_invalid_country":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_country;
                    break;
                case "field_invalid_bank_data":
                    errormessage = paymillcheckout.errormessages.bridge.invalid_bank_data;
                    break;
            }

            return $("<div/>").html(errormessage).text();
        }
    });