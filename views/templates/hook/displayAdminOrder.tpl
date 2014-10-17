{if $paymill[0]['preauth'] ne '' || $paymill[0]['transaction'] ne ''}
<div class="row">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-credit-card"></i>
                 {l s='PAYMILL' mod='pigmbhpaymill'}
            </div>
            <div class="well hidden-print">
                {if $paymill[0]['preauth'] ne ''}
                    <form method='POST' action="{$smarty.server.REQUEST_URI|escape:htmlall}">
                        <input type="hidden" name='id_order' value="{$orderId}">
                        <button type="submit" class="btn btn-default" name="paymillCapture" onclick="if (!confirm('{l s='Are you sure you want to capture?' mod='pigmbhpaymill'}'))return false;">
                            {l s='Capture' mod='pigmbhpaymill'}
                        </button>
                    </form>
                {/if}
                {if $paymill[0]['transaction'] ne ''}
                <form method='POST' action="{$smarty.server.REQUEST_URI|escape:htmlall}">
                    <input type="hidden" name='id_order' value="{$orderId}">
                    <button type="submit" class="btn btn-default" name="paymillRefund" onclick="if (!confirm('{l s='Are you sure you want to refund?' mod='pigmbhpaymill'}'))return false;">
                        {l s='Refund' mod='pigmbhpaymill'}
                    </button>
                </form>
                {/if}
            </div>
        </div>
    </div>
</div>
{/if}