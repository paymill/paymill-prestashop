{$valid_key}
{if $creditcard && $valid_key}
<p class="payment_module">
    <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'creditcard'])}" title="{l s='Paymill Creditcard' mod='pigmbhpaymill'}">
        {l s='Paymill Creditcard' mod='pigmbhpaymill'}
    </a>
</p>
{/if}
{if $debit && $valid_key}
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'debit'])}" title="{l s='Paymill Directdebit' mod='pigmbhpaymill'}">
            {l s='Paymill Directdebit' mod='pigmbhpaymill'}
        </a>
    </p>
{/if}