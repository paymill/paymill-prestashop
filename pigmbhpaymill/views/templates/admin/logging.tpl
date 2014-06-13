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

<div class="paymill_center" style="height: 130px;position:relative">
    <img src="https://static.paymill.com/r/f215617e88bb5c66dcc3bd0bf94be172ea052ac6/img/paymill-logo.png">
    <span style="position:absolute;top:35px;left:0px;">
        <p>{$paymilldescription|escape:'UTF-8'}</p>
        <a href="https://www.paymill.com" class="button" target="_blank">Register Account</a>
    </span>
</div>

{$config|escape:'UTF-8'}

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
        <input type="text" name="searchvalue" value="{$paymillSearchValue|escape:'html'}" style="width:20%">
        <select name="paymillpage">
                {foreach from=$paymillMaxPage item=page key=key}
        <option{if $paymillCurrentPage == $page} selected{/if}>{$page|escape:'intval'}</option>
                {/foreach}
        </select>
        <input type="checkbox" name="connectedsearch" {if $paymillConnectedSearch === "on"}checked{/if}> {l s='Get connected data for matches' mod='pigmbhpaymill'}
        <input type="submit" style="float:right;" value="{l s='Search and goto page' mod='pigmbhpaymill'}">
    </fieldset>
</form>

<br>

{if $showDetail}
    <fieldset class="paymill_center">
        <legend>{$detailData.title|upper|escape}</legend>
        <pre>{$detailData.data|escape:'htmlall'}</pre>
    </fieldset>
{/if}