{*
**
* 2010-2020 Webkul.
*
* NOTICE OF LICENSE
*
* All right is re
* served,
* Please go through this link for complete license : https://store.webkul.com/license.html
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to newer
* versions in the future. If you wish to customize this module for your
* needs please refer to https://store.webkul.com/customisation-guidelines/ for more information.
*
* @author    Webkul IN <support@webkul.com>
* @copyright 2010-2020 Webkul IN
* @license   https://store.webkul.com/license.html
*}

{if isset($shipping_msg)}
  <div class="" id="wk-afs-shipping-alert">
    <p class="alert alert-success" style="font-size: 15px; {if isset($message_css)}
    margin-top: 15px; margin-bottom: -5px; {/if} text-align: left; margin-bottom: 12px;">
        {l s='Add ' mod='vitatiendashipping'}{$currency_symbol}{$required_amt_free_shipping}
        {l s='more to avail free shipping.' mod='vitatiendashipping'}
    </p>
  </div>
{/if}
