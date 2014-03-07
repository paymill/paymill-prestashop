{*Plugin configuration*}
{$config}

<br>

<form id="paymill_logging" method="post">
    <fieldset class="paymill_center">
        <legend>Log</legend>
        <table class="paymill_center">
            <tr>
                <th class="dataTableHeadingContent">IDENTIFIER</th>
                <th class="dataTableHeadingContent">DATE</th>
                <th class="dataTableHeadingContent">MESSAGE</th>
                <th class="dataTableHeadingContent">DEBUG</th>
            </tr>
            {foreach from=$data item=row}
            <tr>
                <td class="dataTableContent">{$row.identifier}</td>
                <td class="dataTableContent">{$row.date}</td>
                <td class="dataTableContent">{$row.message}</td>
                <td class="dataTableContent">{$row.debug}</td>
            </tr>
            {foreachelse}
            <tr>
                <td colspan="4" class="dataTableContent">-</td>
            </tr>
            {/foreach}
        </table>
        <input type="text" name="searchvalue" value="{$paymillSearchValue}" style="width:20%">
        <select name="paymillpage">
                {foreach from=$paymillMaxPage item=page key=key}
        <option{if $paymillCurrentPage == $page} selected{/if}>{$page}</option>
                {/foreach}
        </select>
        <input type="checkbox" name="connectedsearch" {if $paymillConnectedSearch === "on"}checked{/if}> {l s='Get connected data for matches' mod='pigmbhpaymill'}
        <input type="submit" style="float:right;" value="{l s='Search and goto page' mod='pigmbhpaymill'}">
    </fieldset>
</form>

<br>

{if $showDetail}
    <fieldset class="paymill_center">
        <legend>{$detailData.title|upper}</legend>
        <pre>{$detailData.data}</pre>
    </fieldset>
{/if}