<script type="text/javascript" src="{$bridge_url}"></script>
<script type="text/javascript">
    $(document).ready(function() {
    $("#submitButton").click(function(event) {
        var form = $("#submitForm");
        form.append("<input type='hidden' name='paymillToken' value='dummyToken'/>");
        form.append("<input type='hidden' name='payment' value='{$payment}' />");
        form.submit();
    });

    function debug(message){
    {if $paymill_debugging == 'true'}
        {if $payment == 'creditcard'}
        console.log('[PaymillCC] ' + message);
        {elseif $payment == 'debit'}
        console.log('[PaymillELV] ' + message);
        {/if}
    {/if}
}
});
</script>
<link rel="stylesheet" type="text/css" href="{$components}paymill_styles.css" />
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
            {if $paymill_show_label == 'true'}
                <p><div class="paymill_powered"><div class="paymill_credits">{l s='Save creditcardpayment powered by' mod='pigmbhpaymill'} <a href="http://www.paymill.de" target="_blank">Paymill</a></div></div></p>
            {/if}
        </div>
        <p class="cart_navigation">
            <a href="{$link->getPageLink('order', true, ['step'=> '3'])}" class="button_large">{l s='Payment selection' mod='pigmbhpaymill'}</a>
            <input type="button" id='submitButton' value="{l s='Order' mod='pigmbhpaymill'}" class="exclusive_large" />
        </p>
    </form>
{/if}