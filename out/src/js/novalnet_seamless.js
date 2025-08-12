/**
 * Novalnet payment module
 *
 * This file is used for loading Seamless payment form.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: novalnet_seamless.js
 */
var submitButton = document.querySelector('#payment button[type="submit"]');
submitButton.style.display = 'none';

window.onload = function() {
    var unchecked = true;
    var paymentMethods = document.querySelectorAll('input[name="paymentid"]');
    if ($("input[name ='paymentid']:checked").val() == 'novalnetpayments') {
        unchecked = false;
        submitButton.style.display = 'none';
    } else {
        submitButton.style.display = 'block';
    }
    const postMessageData = {
        iframe : '#novalnetiframe',
        initForm: {
            orderInformation : {
                lineItems: JSON.parse($('#orderDetails').val()),
                billing: {}
            },
            uncheckPayments: unchecked,
            setWalletPending: true,
            showButton: false,
        },
    };
    const v13PaymentForm = new NovalnetPaymentForm();
    // Initiate form
    v13PaymentForm.initiate(postMessageData);
    
    paymentMethods.forEach((function(payment) {
        if (payment.checked == false && payment.value == 'novalnetpayments' ) {
            v13PaymentForm.uncheckPayment();
        }
        payment.addEventListener('click', (el) => {
            if (el.target.value != 'novalnetpayments') {
                v13PaymentForm.uncheckPayment();
            }
        });
    }));
    // Receive wallet payments like gpay and applepay response
    v13PaymentForm.walletResponse({
        onProcessCompletion: async (response) => {
            if(response.result.status == 'FAILURE' || response.result.status == 'ERROR' ) {
                $('#novalnet_payment_error').val(response.result.message);
                return {status: 'FAILURE', statusText: 'failure'};
            } else {
                $('#novalnet_payment_details').val(JSON.stringify(response));
                $('#novalnetiframe').closest('form').submit();
                return {status: 'SUCCESS', statusText: 'successfull'};
            }
        }
    });

    // Receive form selected payment action
    v13PaymentForm.selectedPayment(
        (data)=>{
            submitButton.style.display = 'block';
            if ($("input[name ='paymentid']:checked").val() != 'novalnetpayments') {
                document.querySelector('#payment_novalnetpayments').checked = true;
                $( "#payment_novalnetpayments" ).trigger( "click" );
            }
            if (submitButton != undefined && data.payment_details.type == 'GOOGLEPAY' || data.payment_details.type == 'APPLEPAY')
            {
                submitButton.style.display = 'none';
            } else {
                submitButton.style.display = 'block';
            }
        }
    );

    submitButton.addEventListener('click', (e) => {
        if ($("input[name ='paymentid']:checked").val() == 'novalnetpayments') {
            e.preventDefault();
            e.stopImmediatePropagation();
            // Callback for checkout button clicked
            v13PaymentForm.getPayment(
                (data)=>{
                    if(data.result.status == 'ERROR') {
                        $('#novalnet_payment_error').val(data.result.message);
                    }
                        $('#novalnet_payment_details').val(JSON.stringify(data));
                        $('#novalnetiframe').closest('form').submit();
                }
            );
        }
    });
}
