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

<a class="list-action-enable {if $value} action-enabled {else} action-disabled {/if}"
href="index.php?tab=VitatiendaShippingCondition&id_condition={$tableRow.id_condition}&changeHandlingChargeVal&token={$token}">
	{if $value}<i class="icon-check"></i>{else}<i class="icon-remove"></i>{/if}
</a>