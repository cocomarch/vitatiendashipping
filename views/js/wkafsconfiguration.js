/**
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
 */

$(document).ready(function() {
    if (VITATIENDA_SHIPPING_ADMIN_APPROVE=='') {
        $('.wk-show-hide').closest('.form-group').hide();
    }
    $(document).on("click", "input[name='VITATIENDA_SHIPPING_ADMIN_APPROVE']", function() {
        //To show price & page section on enable/disable
        if (document.getElementById('VITATIENDA_SHIPPING_ADMIN_APPROVE_on').checked) {
            $("input[name='PS_SHIPPING_FREE_PRICE']").val(PS_SHIPPING_FREE_PRICE);
            $('.wk-show-hide').closest('.form-group').show();
        } else {
            $("input[name='PS_SHIPPING_FREE_PRICE']").val(0);
            $('.wk-show-hide').closest('.form-group').hide();
        }
    });
    $(document).on("click", "#wkZoneCheckAll", function() {
        //To select all zone at one click
        $(".wk-zone-checkbox-class").prop('checked', $(this).prop('checked'));
        getCountryByZone()
    });
});

function getCountryByZone()
{
    $(".wk-zone-checkbox-class").change(function(){
        if ($('.wk-zone-checkbox-class:checked').length == $('.wk-zone-checkbox-class').length) {
            $("#wkZoneCheckAll").prop('checked', true);
        } else {
            $("#wkZoneCheckAll").prop('checked', false);
        }
    });
    var idsArr = new Array();
    var idCountry = new Array();
    $('input[name="id_zones"]:checked').each(function() {
        idsArr.push(this.value);
    });
    $('#zoneIds').val(idsArr);
    $('#countryData :selected').each(function() {
        idCountry.push(this.value);
    });
    var selectedCountry = $('#addedCountryIds').val();
    if (selectedCountry != '') {
        var newSelectedCountryIds = idCountry+','+selectedCountry;
    } else {
        var newSelectedCountryIds = idCountry+'';
    }
    var newSelectedCountryIdsArr = newSelectedCountryIds.split(',');
    document.getElementById("afs-country-loader").style.display = "block";
    $.ajax({
        url: 'index.php',
        type : 'POST',
        cache : false,
        data : {
            ajax : true,
            controller : 'VitatiendaShippingCondition',
            id_zone : idsArr,
            action : 'getCountryByZoneId',
            token : token,
        },
        success : function (data) {
            if (data!='') {
                var options = '';
                var obj = JSON.parse(data);
                if (obj.length>0) {
                    for (var i = 0; i < obj.length; i++) {
                        for (var k = 0; k < newSelectedCountryIdsArr.length; k++) {
                            if (newSelectedCountryIdsArr[k] == obj[i].id_country) {
                                var selected = "selected";
                                break;
                            } else {
                                var selected = "";
                            }
                        }
                        options += "<option value="+obj[i].id_country+" "+selected+">"+obj[i].name+"</option>";
                    }
                    $('#countryData').html(options);
                    $('#countryData').attr('data-placeholder', afsPlacholder).trigger('chosen:updated');
                    $('#afs-country-loader').hide();
                    $('#countryByZone').show();
                } else {
                    $('#afs-country-loader').hide();
                    $('#countryData').html("<option value=''>"+noRecord+"</option>");
                }
            } else {
                $('#afs-country-loader').hide();
                $('#countryByZone').hide();
                $('#countryData').html("<option value=''>"+chooseValue+"</option>");
            }
        }
    });
}
