{*Plugin configuration*}
{$config}

<form id="paymill_logging" method="post">
    <table>
    {foreach from=$data item=row }
        <tr>
        {foreach from=$row item=cell key=key}
            <td>{$key|upper}</td>
        {/foreach}
        </tr>
    {/foreach}
    {foreach from=$data item=row }
        <tr>
        {foreach from=$row item=cell key=key}
            <td>{$cell}</td>
        {/foreach}
        </tr>
    {/foreach}
    </table>
    <input type="text" name="searchvalue">
    <input type="submit" value="{l s='search' mod='pigmbhpaymill'}">
    <select name="page">
        <option>1</option>
        <option>2</option>
    </select>
</form>