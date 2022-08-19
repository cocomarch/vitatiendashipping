{*
**
* 2010-2020 Webkul.
*
* NOTICE OF LICENSE
*
* All right is reserved,
* Please go through this link for complete license : https://store.webkul.com/license.html
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to https://store.webkul.com/customisation-guidelines/ for more information.
*
*  @author    Webkul IN <support@webkul.com>
*  @copyright 2010-2020 Webkul IN
*  @license   https://store.webkul.com/license.html
*
*}

<form id="vitatiendashipping_condition_form" class="defaultForm form-horizontal VitatiendaShippingCondition" action="" method="post">
    <input type="hidden" name="id_condition" id="id_condition" value="{if isset($conditionData)}{$conditionData.id_condition}{/if}">
    <input type="hidden" name="submitAddvitatiendashipping_condition" value="1">
    <div class="panel" id="fieldset_0">
        <div class="panel-heading">
         <i class="icon-cogs"></i> {l s='Add New Condition' mod='vitatiendashipping'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Name' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                <input type="text" name="name" id="name" value="{if isset($conditionData)}{$conditionData.name}{elseif isset($smarty.post.name)}{$smarty.post.name}{/if}" class="" maxlength="255">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Free shipping start from price' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                    <div class="col-lg-3" style="padding: 0px">
                        <div class="wk-afs-shipping-price">
                            <input type="text" name="shipping_price" id="shipping_price" value="{if isset($conditionData)}{$conditionData.shipping_price}{elseif isset($smarty.post.shipping_price)}{$smarty.post.shipping_price}{/if}" class="input wk-afs-shipping-price">
                        </div>
                    </div>
                    <div class="col-lg-1" style="left: -1%;">
                        <select name="currency">
                            {if isset($currencyData)}
                            {foreach $currencyData as $currencies}
                                <option value="{$currencies.id_currency}"
                                {if isset($conditionData) && $conditionData.id_currency == $currencies.id_currency}
                                selected {elseif $currencies.id_currency == $defaultCurrency} selected {/if}>
                                {$currencies.sign}
                                </option>
                            {/foreach}
                            {/if}
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Free shipping start from weight' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                    <div class="input-group input fixed-width-lg wk-afs-shipping-price">
                        <input type="text" name="shipping_weight" id="shipping_weight" value="{if isset($conditionData)}{$conditionData.shipping_weight}{elseif isset($smarty.post.shipping_weight)}{$smarty.post.shipping_weight}{/if}" class="input fixed-width-lg wk-afs-shipping-price">
                        <span class="input-group-addon">
                            {l s='kg' mod='vitatiendashipping'}
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="
                    {l s='If Yes, then display shipping tax on cart page.' mod='vitatiendashipping'}">
                        {l s='Tax includes' mod='vitatiendashipping'}
                    </span>
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="tax_inc" id="tax_inc_on" value="1"
                        {if isset($conditionData) && $conditionData.tax_inc == 1} checked="checked" {/if}>
                        <label for="tax_inc_on">{l s='Yes' mod='vitatiendashipping'}</label>
                        <input type="radio" name="tax_inc" id="tax_inc_off" value="0"
                        {if isset($conditionData) && $conditionData.tax_inc == 0} checked="checked"
                        {elseif empty($conditionData)} checked="checked" {/if}>
                        <label for="tax_inc_off">{l s='No' mod='vitatiendashipping'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="
                    {l s='If Yes, then handling charges under shipping preferences gets applied on cart page.'
                    mod='vitatiendashipping'}">
                        {l s='Handling charges' mod='vitatiendashipping'}
                    </span>
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="handling_charge" id="handling_charge_on" value="1"
                        {if isset($conditionData) && $conditionData.handling_charge == 1} checked="checked" {/if}>
                        <label for="handling_charge_on">{l s='Yes' mod='vitatiendashipping'}</label>
                        <input type="radio" name="handling_charge" id="handling_charge_off" value="0"
                        {if isset($conditionData) && $conditionData.handling_charge == 0} checked="checked"
                        {elseif empty($conditionData)} checked="checked" {/if}>
                        <label for="handling_charge_off">{l s='No' mod='vitatiendashipping'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Select zone' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                {if isset($zoneData)}
                <div class="checkbox">
                    <label for="id_zones_all">
                        <input type="checkbox" id="wkZoneCheckAll" /> {l s='Select All' mod='vitatiendashipping'}
                    </label>
                </div>
                {foreach $zoneData as $zones}
                <div class="checkbox">
                    <label for="id_zones_{$zones.id_zone}">
                    <input type="checkbox" name="id_zones" id="id_zones_{$zones.id_zone}"
                    class="wk-zone-checkbox-class" onclick="getCountryByZone();" {if isset($addedZone) && in_array($zones.id_zone, $addedZone)} checked="checked" {/if} value="{$zones.id_zone}">
                    {$zones.name}
                    </label>
                </div>
                {/foreach}
                {/if}
                <input type="hidden" name="zoneIds" id="zoneIds" class="" value="{if isset($addedZoneStr)}{$addedZoneStr}{/if}">
                <span id="afs-country-loader" style="margin-top: 8px; margin-bottom: -8px; font-size: 14px;
                font-weight: bold; color: green; display:none;">{l s='Loading...' mod='vitatiendashipping'}</span>
                </div>
            </div>
            <div class="form-group" id="countryByZone" {if isset($editMsg)} {else} style="display:none;" {/if}>
                <label class="control-label col-lg-3">{l s='Select country' mod='vitatiendashipping'}</label>
                <div class="col-lg-9">
                    <select name="country[]" class="form-control chosen" data-placeholder="{l s='Click to choose' mod='vitatiendashipping'}" id="countryData" style="height: 125px;" multiple>
                    {if isset($sortedCountryList)}
                    {foreach $sortedCountryList as $sortedCountry}
                        <option value="{$sortedCountry.id_country}" {if isset($addedCountry) && in_array($sortedCountry.id_country, $addedCountry)} selected="selected" {/if}>
                        {$sortedCountry.name}
                        </option>
                    {/foreach}
                    {/if}
                    </select>
                    <input type="hidden" name="addedCountryIds" id="addedCountryIds" class="" value="{if isset($addedCountryIds)}{$addedCountryIds}{/if}">
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3 required">
                    {l s='Select carrier' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                {if isset($carrierData)}
                {foreach $carrierData as $carriers}
                <div class="checkbox">
                    <label for="id_carriers_{$carriers.id_carrier}">
                    <input type="checkbox" name="id_carriers[]"
                    id="id_carriers_{$carriers.id_carrier}" class=""
                    {if isset($addedCarriers) && in_array($carriers.id_carrier, $addedCarriers)} checked="checked"
                    {/if} value="{$carriers.id_carrier}">
                    {$carriers.name}
                    </label>
                </div>
                {/foreach}
                {/if}
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Select group' mod='vitatiendashipping'}</label>
                <div class="col-lg-9">
                {if isset($groupData)}
                {foreach $groupData as $groups}
                <div class="checkbox">
                    <label for="id_groups_{$groups.id_group}">
                    <input type="checkbox" name="id_groups[]"
                    id="id_groups_{$groups.id_group}" class=""
                    value="{$groups.id_group}"
                    {if isset($addedGroups) && in_array($groups.id_group, $addedGroups)} checked="checked" {/if}>
                    {$groups.name}
                    </label>
                </div>
                {/foreach}
                {/if}
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Active' mod='vitatiendashipping'}
                </label>
                <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" name="active" id="active_on" value="1"
                        {if isset($conditionData) && $conditionData.active == 1} checked="checked" {/if}>
                        <label for="active_on">{l s='Yes' mod='vitatiendashipping'}</label>
                        <input type="radio" name="active" id="active_off" value="0"
                        {if isset($conditionData) && $conditionData.active == 0} checked="checked"
                        {elseif empty($conditionData)} checked="checked" {/if}>
                        <label for="active_off">{l s='No' mod='vitatiendashipping'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" value="1" id="vitatiendashipping_condition_form_submit_btn" name="submitCondition"
            class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Save' mod='vitatiendashipping'}
            </button>
            <a href="#" class="btn btn-default" id="vitatiendashipping_condition_form_cancel_btn" onclick="window.history.back();">
            <i class="process-icon-cancel"></i> {l s='Cancel' mod='vitatiendashipping'}
            </a>
        </div>
    </div>
</form>
