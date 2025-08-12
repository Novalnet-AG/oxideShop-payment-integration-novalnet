/**
 * Novalnet payment module
 *
 * This file is used for follow up process of orders in the shop admin
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: novalnet_order.js
 */

/**
 * Confirms the manage transaction process
 *
 * @returns {boolean}
 */
function validateManageProcess()
{
    if (jQuery('#dNovalnetManageStatus').length && jQuery('#dNovalnetManageStatus').val() == '') {
        return false;
    }
    var sConfirmMessage = (jQuery('#dNovalnetManageStatus').val() == '100') ? jQuery('#sNovalnetConfirmCapture').val() : jQuery('#sNovalnetConfirmCancel').val();
    if (!confirm(sConfirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    return true;
}

/**
 * Confirms the refund process
 *
 * @returns {boolean}
 */
function validateRefundProcess()
{
    if (jQuery('#novalnet_refund_amount').val() == '' || jQuery('#novalnet_refund_amount').val() < 1) {
        return false;
    }
    var confirmMessage = jQuery('#novalnet_confirm_refund').val();
    if (!confirm(confirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    return true;
}

/**
 * Confirms the zero-amount booking process
 *
 * @returns {boolean}
 */
function validateBookProcess()
{
    if (jQuery('#novalnet_book_amount').val() == '' || jQuery('#novalnet_book_amount').val() < 1) {
        return false;
    }
    var confirmMessage = jQuery('#novalnet_confirm_book_amount').val();
    if (!confirm(confirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    return true;
}

/**
 * Validates the triggered key is numeric
 *
 * @returns {boolean}
 */
function isValidExtensionKey(event)
{
    var keycode = ('which' in event) ? event.which : event.keyCode;
    var reg = /^(?:[0-9]+$)/;
    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114)) ? true : false;
}

/**
 * Show a refund process details
 */
function refundProcessDetail(count)
{
    jQuery('#refund_box_' + count).css('display', 'block');
    jQuery('#refund_action_' + count).css('display', 'none');
}

/**
 * Confirm a refund process
 */
function refundProcessDetailConfirm(count)
{
    var confirmMessage = jQuery('#novalnet_confirm_refund').val();
    if (!confirm(confirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    var params = {
        'tid': jQuery('#refund_tid_' + count).val(),
        'action': 'nn_refund',
        'reason': jQuery('#nn_refund_reason_' + count).val(),
        'amount': jQuery('#nn_refund_amount_' + count).val(),
        'parent_tid': jQuery('#parent_tid').val(),
        'order_id': jQuery('#order_id').val(),
    };
    return sendAjaxCall(params);
}

/**
 * Cancel all instalment cycle
 */
function cancelAllInstalment(tid)
{
    var confirmMessage = jQuery('#cancel_all_instalment').val();
    if (!confirm(confirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    var params = {
        'tid': tid,
        'action': 'cancel_all_instalment',
        'parent_tid': jQuery('#parent_tid').val(),
        'order_id': jQuery('#order_id').val(),
    };
    return sendAjaxCall(params);
}

/**
 * Cancel remaining instalment cycle
 */
function cancelRemainingInstalment(tid)
{
    var confirmMessage = jQuery('#cancel_remaining_instalment').val();
    if (!confirm(confirmMessage)) {
        return false;
    } else {
        jQuery('.extsubmit').attr('disabled',true);
    }
    var params = {
        'tid': tid,
        'action': 'cancel_remaining_instalment',
        'parent_tid': jQuery('#parent_tid').val(),
        'order_id': jQuery('#order_id').val(),
    };
    return sendAjaxCall(params);
}



/**
 * Show a instalment cancel type
 */
function instalmentCancelTypeShow()
{
    jQuery('#instalment_cancel').css('display', 'none');
    jQuery('#all_instalment_cancel').css('display', 'block');
    jQuery('#remaining_instalment_cancel').css('display', 'block');
}

/**
 * Show a refund process details
 */
function refundProcessDetailCancel(count)
{
    jQuery('#refund_box_' + count).css('display', 'none');
    jQuery('#refund_action_' + count).css('display', 'block');
}

/**
 * Send a ajax call for the follow up process
 */
function sendAjaxCall(params)
{
    var shopurl  = jQuery('#getShopUrl').val();
    var sToken   = jQuery('#sToken').val();
    var selflink  = jQuery('#getSelfLink').val();

    var formurl  = shopurl + "index.php?cl=novalnet_order&fnc=performNovalnetAction&stoken=" + sToken;
    jQuery.ajax(
        {
            url: formurl,
            type: 'post',
            dataType: 'json',
            data: {
                serverResponse : JSON.stringify(params)
            },
            success: function (result) {
                if (result.success == true) {
                    window.location.reload(selflink);
                } else {
                    alert(result.text);
                    window.location.reload(selflink);
                    return true;
                }
            }
        }
    );
}
