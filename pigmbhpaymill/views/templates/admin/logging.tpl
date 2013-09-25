{*Plugin configuration*}
{$config}



{if $showDetail}
    <fieldset class="paymill_center">
        <legend>{$detailData['title']|upper}</legend>
        <pre>{$detailData['data']}</pre>
    </fieldset>
{/if}

<form id="paymill_logging" method="post">
    <fieldset class="paymill_center">
        <legend>Log</legend>
        <table class="paymill_center">
                {foreach from=$data item=row }
            <tr>
                {foreach from=$row item=cell key=key}
                <th class="dataTableHeadingContent">{$key|upper}</th>
                {/foreach}
            </tr>
            <tr>
                {foreach from=$row item=cell key=key}
                <td class="dataTableContent">{$cell}</td>
                {/foreach}
            </tr>
                {/foreach}
        </table>
        <input type="text" name="searchvalue" value="{$paymillSearchValue}" style="width:20%">
        <select name="paymillpage">
                    {foreach from=$paymillMaxPage item=page key=key}
        <option{if $paymillCurrentPage == $page} selected{/if}>{$page}</option>
                    {/foreach}
        </select>
        <input type="submit" value="{l s='submit' mod='pigmbhpaymill'}">
    </fieldset>
</form>