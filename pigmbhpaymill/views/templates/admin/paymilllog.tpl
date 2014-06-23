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
<form id="paymill_logging" method="post">
    <fieldset>
        <legend>Log</legend>
        <table width="100%">
            <tr>
                <th class="dataTableHeadingContent">IDENTIFIER</th>
                <th class="dataTableHeadingContent">DATE</th>
                <th class="dataTableHeadingContent">MESSAGE</th>
                <th class="dataTableHeadingContent">DEBUG</th>
            </tr>
            {foreach from=$logging.data item=row}
            <tr>
                <td class="dataTableContent">{$row.identifier|escape:'intval'}</td>
                <td class="dataTableContent">{$row.date|escape:'html'}</td>
                <td class="dataTableContent">{$row.message|escape:'UTF-8'}</td>
                <td class="dataTableContent">{$row.debug|escape:'UTF-8'}</td>
            </tr>
            {foreachelse}
            <tr>
                <td colspan="4" class="dataTableContent">-</td>
            </tr>
            {/foreach}
        </table>
        <input type="text" name="searchvalue" value="{$logging.paymill_searchvalue|escape:'html'}" style="width:20%">
        <select name="paymillpage">
                {foreach from=$logging.paymill_maxpage item=page key=key}
        <option{if $logging.paymill_currentpage == $page} selected{/if}>{$page|escape:'intval'}</option>
                {/foreach}
        </select>
        <input type="checkbox" name="connectedsearch" {if $logging.paymill_connectedsearch === "on"}checked{/if}> {l s='Get connected data for matches' mod='pigmbhpaymill'}
        <input type="submit" style="float:right;" value="{l s='Search and goto page' mod='pigmbhpaymill'}">
    </fieldset>
</form>

{if $logging.show_detail}
    <fieldset>
        <legend>{$logging.detail_data.title|upper|escape}</legend>
        <pre>{$logging.detail_data.data|escape:'htmlall'}</pre>
    </fieldset>
{/if}