/**
 * Novalnet payment module
 *
 * This file is used for API calls of auto config and webhook URL config.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: novalnet_config.js
 */

/**
 * Toggles the Novalnet payment configuration
 */
jQuery(document).ready(
    function () {
        setNovalnetConfig();
        jQuery("input[name='confstrs[sProductActivationKey]'], input[name='confstrs[sPaymentAccessKey]']").change(
            function () {
                setNovalnetConfig();
            }
        );
        if (jQuery('form#moduleConfiguration').length) {
            jQuery("#moduleConfiguration").submit(
                function () {
                    var sNovalnetActivationKey = jQuery.trim(jQuery("input[name='confstrs[sProductActivationKey]']").val());
                    var sNovalnetAccessKey = jQuery.trim(jQuery("input[name='confstrs[sPaymentAccessKey]']").val());
                    var sErrorMsg = jQuery('#sMandatoryError').val();
                    if (sNovalnetActivationKey == '' || sNovalnetAccessKey == '') {
                        alert(sErrorMsg);
                        return false;
                    }
                }
            );
        }
    }
);

/**
 * Update webhook url
 */
function setWebhookConfig()
{
    var sProductActivationKey = jQuery.trim(jQuery("#sProductActivationKey").val());
    var sWebhooksUrl = jQuery.trim(jQuery("#sWebhooksUrl").val());
    var sAccessKey = jQuery.trim(jQuery("#sPaymentAccessKey").val());
    if (sProductActivationKey != '' && sWebhooksUrl != '' && sAccessKey != '') {
        var sToken   = jQuery('#sToken').val();
        var oParams   = { 'activationKey': sProductActivationKey, 'webhookUrl': sWebhooksUrl , 'accessKey' : sAccessKey};
        var sShopUrl  = jQuery('#sGetUrl').val();
        var sFormUrl  = sShopUrl + "index.php?cl=novalnetconfiguration&fnc=updateWebhookUrl&stoken=" + sToken;
        var sSuccessMsg = jQuery('#sWebhookSuccess').val();
        jQuery.ajax(
            {
                url: sFormUrl,
                type: 'POST',
                data: oParams,
                dataType: 'json',
                success: function (resultData) {
                    if (resultData.details == 'true') {
                        alert(sSuccessMsg);
                    } else {
                        alert(resultData.error);
                    }
                }
            }
        );
    }
}

/**
 * Sets the Novalnet credentials
 */
function setNovalnetConfig()
{
    var sNovalnetActivationKey = jQuery.trim(jQuery("input[name='confstrs[sProductActivationKey]']").val());
    var sNovalnetAccessKey = jQuery.trim(jQuery("input[name='confstrs[sPaymentAccessKey]']").val());
    if (sNovalnetActivationKey != '' && sNovalnetAccessKey != '') {
        getMerchantConfigs(sNovalnetActivationKey, sNovalnetAccessKey);
    }
}

/**
 * Sends the api call to get the vendor credentials
 */
function getMerchantConfigs(sNovalnetActivationKey, sNovalnetAccessKey)
{
    var sToken   = jQuery('#sToken').val();
    var sShopUrl  = jQuery('#sGetUrl').val();
    var oParams   = { 'hash': sNovalnetActivationKey, 'accessKey': sNovalnetAccessKey };
    if (sShopUrl != '' && sToken != '') {
        var sFormUrl  = sShopUrl + "index.php?cl=novalnetconfiguration&fnc=getMerchantDetails&stoken=" + sToken;
        jQuery.ajax(
            {
                url: sFormUrl,
                type: 'POST',
                data: oParams,
                dataType: 'json',
                success: function (resultData) {
                    if (resultData.details == 'true') {
                        var response = resultData.response;
                        if (response.merchant.vendor != undefined && response.merchant.project != undefined) {
                            var tariff = response.merchant.tariff;
                            var sWebhooksUrl = sShopUrl + "?cl=novalnetcallback&fnc=handlerequest";
                            jQuery('#sWebhooksUrl').val(sWebhooksUrl);
                            var novalnetSavedTariff = jQuery('#novalnetSavedTariff').val();
                            jQuery("#dNovalnetTariffId option").remove();
                            jQuery.each(
                                tariff,
                                function ( index, value ) {
                                    jQuery('<option/>', { text  : value.name, value : index }).appendTo('#dNovalnetTariffId');
                                }
                            );
                            if (novalnetSavedTariff != '') {
                                      jQuery('#dNovalnetTariffId').find('option[value=' + novalnetSavedTariff + ']').attr('selected', true);
                            } else {
                                jQuery('#dNovalnetTariffId').val(jQuery.trim(Object.keys(response.merchant.tariff)[0])).attr("selected", true);
                            }
                            if (response.merchant.hook_url == '' || response.merchant.hook_url != sWebhooksUrl) {
                                setWebhookConfig();
                            }
                        }
                    } else {
                            jQuery("input[name='confstrs[sProductActivationKey]'],input[name='confstrs[sPaymentAccessKey]'],#dNovalnetTariffId").val('');
                    }
                }
            }
        );
    }
}
