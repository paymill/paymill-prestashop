{**
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
*}
{if !is_null($orderaction)}
<div class="alert alert-warning">
    {if $orderaction}
        {l s='PAYMILL action was successfull' mod='pigmbhpaymill'}
    {else}
        {l s='PAYMILL action has failed' mod='pigmbhpaymill'}
        <br/>
        {l s='Please check the Log' mod='pigmbhpaymill'}
    {/if}
</div>
{/if}

{if $paymill[0]['preauth'] ne '' || ($paymill[0]['transaction'] ne '' && $paymill[0]['refund'] ne 1)}
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
                {if ($paymill[0]['transaction'] ne '' && $paymill[0]['refund'] ne 1)}
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
