{if $creditcard === 'on' && $valid_key}
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'creditcard'])}" title="{l s='Paymill Creditcard' mod='pigmbhpaymill'}">
            <img src="{$this_path_ssl}/../logo.gif">
            {l s='Paymill Creditcard' mod='pigmbhpaymill'}
        </a>
        {if $paymillerror && $paymillpayment === "creditcard"}
        <p class="error">{$paymillerror}</p>
        {/if}
    </p>
{/if}
{if $debit === 'on' && $valid_key}
    <p class="payment_module">
        <a href="{$link->getModuleLink('pigmbhpaymill', 'payment', ['payment'=>'debit'])}" title="{l s='Paymill Directdebit' mod='pigmbhpaymill'}">
            <img src="{$this_path_ssl}/../logo.gif">
            {l s='Paymill Directdebit' mod='pigmbhpaymill'}
        </a>
        {if $paymillerror && $paymillpayment === "debit"}
        <p class="error">{$paymillerror}</p>
        {/if}
    </p>
{/if}