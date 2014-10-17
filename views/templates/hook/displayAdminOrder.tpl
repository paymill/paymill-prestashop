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
                <a id='paymill_order_action_capture' class="btn btn-default" href="">
                    <i class="icon-credit-card"></i>
                    {l s='Capture' mod='pigmbhpaymill'}
                </a>
                {/if}
                {if $paymill[0]['transaction'] ne ''}
                <a id='paymill_order_action_refund' class="btn btn-default" href="">
                    <i class="icon-exchange"></i>
                    {l s='Refund' mod='pigmbhpaymill'}
                </a>
                {/if}
            </div>
        </div>
    </div>
</div>
{/if}