<?php
/**
 * Novalnet payment module
 *
 * This file is used for real time processing of transaction.
 *
 * This is free contribution made by request.
 * If you have found this file useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: novalnet_lang.php
 */

$sLangName = 'English';

$aLang = [
    'charset'                       => 'UTF-8',
    'NOVALNET_MENU'                 => 'Novalnet',
    'NOVALNET_CONFIG_MENU'          => 'Novalnet payment configuration',
    'NOVALNET_ADMIN_CONFIG_MESSAGE' => 'For additional configurations login to <a class="novalnet_config_link" target="_blank" href="https://admin.novalnet.de">Novalnet Merchant Administration portal</a>.<br/>To login to the Portal you need to have an account at Novalnet. If you don\'t have one yet, please contact <a href="mailto:sales@novalnet.de">sales@novalnet.de</a> / tel. +49 (089) 923068320<br/><br/>To use the PayPal payment method please enter your PayPal API details in <a class="novalnet_config_link" href="https://admin.novalnet.de" target="_blank">Novalnet Merchant Administration portal</a>',    
    'NOVALNET_LINK_URL'             => 'http://www.novalnet.com',
    'NOVALNET_GLOBAL_CONFIGURATION' => 'Novalnet Global Configuration',
    'NOVALNET_CREDITCARD'           => 'Novalnet Credit Card',
    'NOVALNET_SEPA'                 => 'Novalnet Direct Debit SEPA',
    'NOVALNET_INVOICE'              => 'Novalnet Invoice',
    'NOVALNET_PREPAYMENT'           => 'Novalnet Prepayment',
    'NOVALNET_PAYPAL'               => 'Novalnet PayPal',
    'NOVALNET_INSTANTBANK'          => 'Novalnet Instant Bank Transfer',
    'NOVALNET_IDEAL'                => 'Novalnet iDEAL',
    'NOVALNET_EPS'                  => 'Novalnet eps',
    'NOVALNET_GIROPAY'              => 'Novalnet giropay',
    'NOVALNET_PRZELEWY24'           => 'Novalnet Przelewy24',

    'NOVALNET_PRODUCT_ACTIVATION_KEY_TITLE'       => 'Product activation key <span style="color:red;">*</span>',            
    'NOVALNET_TARIFF_ID_TITLE'                    => 'Tariff ID <span style="color:red;">*</span>',    
    'NOVALNET_MANUAL_CHECK_LIMIT_TITLE'           => 'Minimum transaction limit for authorization (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)',
    'NOVALNET_PROXY_SERVER_TITLE'                 => 'Proxy server',
    'NOVALNET_GATEWAY_TIMEOUT_TITLE'              => 'Gateway timeout (in seconds)',
    'NOVALNET_REFERRER_ID_TITLE'                  => 'Referrer ID',
    'NOVALNET_LOGO_CONFIGURATION_TITLE'           => 'Logos display management',    
    'NOVALNET_CHECKOUT_PAYMENT_LOGO_TITLE'        => 'Display payment method logo',
    'NOVALNET_CALLBACKSCRIPT_CONFIGURATION_TITLE' => 'Merchant script management',
    'NOVALNET_CALLBACK_TEST_MODE_TITLE'           => 'Deactivate IP address control (for test purpose only)',
    'NOVALNET_CALLBACK_TEST_MODE_DESCRIPTION'     => 'This option will allow performing a manual execution. Please disable this option before setting your shop to LIVE mode, to avoid unauthorized calls from external parties (excl. Novalnet).',    
    'NOVALNET_CALLBACK_TO_ADDRESS_TITLE'          => 'E-mail address (To)',
    'NOVALNET_CALLBACK_BCC_ADDRESS_TITLE'         => 'E-mail address (Bcc)',
    'NOVALNET_CALLBACK_ENABLE_MAIL_TITLE'         => 'Enable E-mail notification for callback',
    'NOVALNET_NOTIFY_URL_TITLE'                   => 'Notification URL',
    'NOVALNET_PRODUCT_ACTIVATION_KEY_DESCRIPTION' => 'Enter Novalnet Product activation key.To get the Product Activation Key, go to <a class="novalnet_config_link" target="_blank" href="https://admin.novalnet.de" style="color:#0080c9;cursor:pointer;font-size:11px;font-weight:bold;">Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Shop Parameters: API Signature (Product activation key)',
    'NOVALNET_TARIFF_ID_DESCRIPTION'              => 'Select Novalnet tariff ID',
    'NOVALNET_TEST_MODE_MAIL_DESCRIPTION'         => 'You will receive email notifications about every test order in the web shop',
    'NOVALNET_MANUAL_CHECK_LIMIT_DESCRIPTION'     => 'In case the order amount exceeds the mentioned limit, the transaction will be set on-hold till your confirmation of the transaction. You can leave the field empty if you wish to process all the transactions as on-hold',
    'NOVALNET_PAYPAL_MANUAL_CHECK_LIMIT_DESCRIPTION'  => 'In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction (In order to use this option you must have billing agreement option enabled in your PayPal account. Please contact your account manager at PayPal.)',
    'NOVALNET_PROXY_SERVER_DESCRIPTION'           => 'Enter the IP address of your proxy server along with the port number in the following format IP Address : Port Number (if applicable)',
    'NOVALNET_GATEWAY_TIMEOUT_DESCRIPTION'        => 'In case the order processing time exceeds the gateway timeout, the order will not be placed',
    'NOVALNET_REFERRER_ID_DESCRIPTION'            => 'Enter the referrer ID of the person/company who recommended you Novalnet',
    'NOVALNET_LOGO_CONFIGURATION_DESCRIPTION'     => 'You can activate or deactivate the logos display for the checkout page',
    
    'NOVALNET_CHECKOUT_PAYMENT_LOGO_DESCRIPTION'  => 'The payment method logo will be displayed on the checkout page',
    
    'NOVALNET_CALLBACK_TO_ADDRESS_DESCRIPTION'    => 'E-mail address of the recipient',
    'NOVALNET_CALLBACK_BCC_ADDRESS_DESCRIPTION'   => 'E-mail address of the recipient for BCC',
    'NOVALNET_NOTIFY_URL_DESCRIPTION'             => 'The notification URL is used to keep your database/system actual and synchronizes with the Novalnet transaction status',

    'NOVALNET_TEST_MODE_TITLE'                    => 'Enable test mode',
    'NOVALNET_BUYER_NOTIFICATION_TITLE'           => 'Notification for the buyer',    
    'NOVALNET_FRAUD_MODULE_TITLE'                 => 'Enable fraud prevention',
    'NOVALNET_FRAUD_MODULE_AMOUNT_LIMIT_TITLE'    => 'Minimum value of goods for the fraud module (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)',
    'NOVALNET_CREDITCARD_3D_ACTIVE_TITLE'         => 'Enable 3D secure',
    'NOVALNET_CREDITCARD_3D_FRAUD_ACTIVE_TITLE'   => 'Force 3D secure on predefined conditions',
    'NOVALNET_CREDITCARD_AMEX_TITLE'              => 'Display AMEX logo',
    'NOVALNET_CREDITCARD_MAESTRO_TITLE'           => 'Display Maestro logo',
    'NOVALNET_SHOP_TYPE_TITLE'                    => 'Shopping type',
    
    
    'NOVALNET_SEPA_DUE_DATE_TITLE'                => 'SEPA payment duration (in days)',
    'NOVALNET_INVOICE_DUE_DATE_TITLE'             => 'Payment due date (in days)',
    'NOVALNET_GUARANTEE_CONFIGURATION_TITLE'      => 'Payment guarantee configuration',
    'NOVALNET_GUARANTEE_PAYMENT_TITLE'            => 'Enable payment guarantee',
    'NOVALNET_GUARANTEE_MINIMUM_AMOUNT_TITLE'     => 'Minimum order amount (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)',
    'NOVALNET_GUARANTEE_PAYMENT_FORCE_TITLE'      => 'Force Non-Guarantee payment',

    'NOVALNET_TEST_MODE_DESCRIPTION'                 => 'The payment will be processed in the test mode therefore amount for this transaction will not be charged',
    'NOVALNET_BUYER_NOTIFICATION_DESCRIPTION'        => 'The entered text will be displayed on the checkout page',
    'NOVALNET_FRAUD_MODULE_DESCRIPTION'              => 'To authenticate the buyer for a transaction, the PIN will be automatically generated and sent to the buyer. This service is only available for customers from DE, AT, CH',
    'NOVALNET_FRAUD_MODULE_AMOUNT_LIMIT_DESCRIPTION' => 'Enter the minimum value of goods from which the fraud module should be activated',
    'NOVALNET_CREDITCARD_3D_ACTIVE_DESCRIPTION'      => 'The 3D-Secure will be activated for credit cards. The issuing bank prompts the buyer for a password what, in turn, help to prevent a fraudulent payment. It can be used by the issuing bank as evidence that the buyer is indeed their card holder. This is intended to help decrease a risk of charge-back',
    'NOVALNET_CREDITCARD_3D_FRAUD_ACTIVE_DESCRIPTION'=> 'If 3D secure is not enabled in the above field, then force 3D secure process as per the “Enforced 3D secure (as per predefined filters & settings)” module configuration at the Novalnet Merchant Administration portal. If the predefined filters & settings from Enforced 3DSecure module are met, then the transaction will be processed as 3D secure transaction otherwise it will be processed as non 3D secure.<br>Please note that the “Enforced 3D secure (as per predefined filters & settings)” module should be configured at Novalnet Merchant Administration portal prior to the activation here.<br>For further information, please refer the description of this fraud module at “Fraud Modules” tab, below “Projects” menu, under the selected project in Novalnet Merchant Administration portal or contact novalnet support team.',
    'NOVALNET_CREDITCARD_AMEX_DESCRIPTION'           => 'Display AMEX logo in checkout page',
    'NOVALNET_CREDITCARD_MAESTRO_DESCRIPTION'        => 'Display Maestro logo in checkout page',
    'NOVALNET_SHOP_TYPE_DESCRIPTION'                 => 'Select shopping type',        
    'NOVALNET_SEPA_DUE_DATE_DESCRIPTION'             => 'Enter the number of days after which the payment should be processed (must be between 2 and 14 days)',
    'NOVALNET_INVOICE_DUE_DATE_DESCRIPTION'          => 'Enter the number of days to transfer the payment amount to Novalnet (must be greater than 7 days). In case if the field is empty, 14 days will be set as due date by default',
    'NOVALNET_GUARANTEE_CONFIGURATION_DESCRIPTION'   => 'Basic requirements for payment guarantee
                                                         <ul>
                                                             <li>Allowed countries: AT, DE, CH</li>
                                                             <li>Allowed currency: EUR</li>
                                                             <li>Minimum amount of order >= 9,99 EUR</li>
                                                             <li>Minimum age of end customer >= 18 Years</li>
                                                             <li>The billing address must be the same as the shipping address</li>
                                                             <li>Gift certificates/vouchers are not allowed</li>
                                                         </ul>',
    'NOVALNET_GUARANTEE_MINIMUM_AMOUNT_DESCRIPTION'  => 'This setting will override the default setting made in the minimum order amount. Note: The minimum amount should be 9,99 EUR',
    'NOVALNET_GUARANTEE_PAYMENT_FORCE_DESCRIPTION'   => 'If the payment guarantee is activated (True), but the above mentioned requirements are not met, the payment should be processed as non-guarantee payment',

    'NOVALNET_OPTION_NONE'               => 'None',
    'NOVALNET_FRAUD_MODULE_OPTION_CALL'  => 'PIN by callback',
    'NOVALNET_FRAUD_MODULE_OPTION_SMS'   => 'PIN by SMS',
    'NOVALNET_ONE_CLICK_SHOP'            => 'One click shopping',
    'NOVALNET_ZERO_AMOUNT_BOOK'          => 'Zero amount booking',    
    'NOVALNET_MANAGE_TRANSACTION_TITLE'  => 'Manage transaction process',
    'NOVALNET_MANAGE_TRANSACTION_LABEL'  => 'Please select status',
    'NOVALNET_PLEASE_SELECT'             => '--Select--',
    'NOVALNET_CONFIRM'                   => 'Confirm',
    'NOVALNET_CANCEL'                    => 'Cancel',
    'NOVALNET_UPDATE'                    => 'Update',

    'NOVALNET_UPDATE_AMOUNT_TITLE'       => 'Amount update',
    'NOVALNET_UPDATE_AMOUNT_LABEL'       => 'Update transaction amount',
    'NOVALNET_CENTS'                     => '(in minimum unit of currency.<br> E.g. enter 100 which is equal to 1.00)',

    'NOVALNET_REFUND_AMOUNT_TITLE'                => 'Refund process',
    'NOVALNET_REFUND_AMOUNT_LABEL'                => 'Please enter the refund amount',
    'NOVALNET_REFUND_REFERENCE_LABEL'             => 'Refund reference',
    'NOVALNET_AMOUNT_REFUNDED_PARENT_TID_MESSAGE' => '<br><br>The refund has been executed for the TID: %s with the amount of %s',
    'NOVALNET_AMOUNT_REFUNDED_CHILD_TID_MESSAGE'  => '. Your new TID for the refund amount: %s',
    'NOVALNET_STATUS_UPDATE_CONFIRMED_MESSAGE'    => '<br><br>The transaction has been confirmed on %s, %s',
    'NOVALNET_STATUS_UPDATE_CANCELED_MESSAGE'     => '<br><br>The transaction has been canceled on %s, %s',
    'NOVALNET_AMOUNT_UPDATED_MESSAGE'             => '<br><br><br>The transaction amount %s has been updated successfully on %s, %s',

    'NOVALNET_TRANSACTION_DETAILS'            => 'Novalnet transaction details<br>',
    'NOVALNET_TRANSACTION_ID'                 => 'Novalnet transaction ID: ',
    'NOVALNET_TEST_ORDER'                     => '<br>Test order',
    'NOVALNET_UPDATE_AMOUNT_DUEDATE_TITLE'    => 'Change the  amount / due date',
    'NOVALNET_UPDATE_DUEDATE_LABEL'           => 'Transaction due date',
    'NOVALNET_INVOICE_COMMENTS_TITLE'         => '<br><br>Please transfer the amount to the below mentioned account details of our payment processor Novalnet<br>',
    'NOVALNET_DUE_DATE'                       => '<br>Due date: ',
    'NOVALNET_ACCOUNT'                        => '<br>Account holder: ',
    'NOVALNET_AMOUNT'                         => '<br>Amount: ',
    'NOVALNET_INVOICE_SINGLE_REF_DESCRIPTION' => '<br>Please use the following payment reference for your money transfer, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_MULTI_REF_DESCRIPTION'  => '<br>Please use any one of the following references as the payment reference, as only through this way your payment is matched and assigned to the order:',
    'NOVALNET_INVOICE_SINGLE_REFERENCE'       => '<br>Payment Reference: ',
    'NOVALNET_INVOICE_MULTI_REFERENCE'        => '<br>Payment Reference %s: ',
    'NOVALNET_ORDER_NO'                       => 'Order number ',

    'NOVALNET_INVALID_STATUS'                         => 'Please select status',
    'NOVALNET_INVALID_AMOUNT'                         => 'The amount is invalid',
    'NOVALNET_INVALID_DUEDATE'                        => 'Invalid due date',
    'NOVALNET_INVALID_PAST_DUEDATE'                   => 'The date should be in future',    
    'NOVALNET_INVALID_GUARANTEE_MINIMUM_AMOUNT_ERROR' => 'The minimum amount should be at least 9,99 EUR',
    'NOVALNET_CONFIRM_CAPTURE'                        => 'Are you sure you want to capture the payment?',
    'NOVALNET_CONFIRM_CANCEL'                         => 'Are you sure you want to cancel the payment?',
    'NOVALNET_CONFIRM_AMOUNT_UPDATE'                  => 'Are you sure you want to change the order amount?',
    'NOVALNET_CONFIRM_DUEDATE_UPDATE'                 => 'Are you sure you want to change the order amount or due date?',
    'NOVALNET_CONFIRM_REFUND'                         => 'Are you sure you want to refund the amount?',
    'NOVALNET_CONFIRM_BOOKED'                         => 'Are you sure you want to book the order amount?',

    'NOVALNET_BOOK_AMOUNT_TITLE'                      => 'Book transaction',
    'NOVALNET_BOOK_AMOUNT_LABEL'                      => 'Transaction booking amount',
    'NOVALNET_AMOUNT_BOOKED_MESSAGE'                  => '<br><br>Your order has been booked with the amount of %s. Your new TID for the booked amount: %s',
    'NOVALNET_INVALID_CONFIG_ERROR'                   => 'Please fill in all the mandatory fields',
    'NOVALNET_INVALID_SEPA_CONFIG_ERROR'              => 'SEPA Due date is not valid ',    
    'NOVALNET_DEFAULT_ERROR_MESSAGE'                  => 'Payment was not successful. An error occurred',   
    'NOVALNET_IFRAME_CONFIGURATION_TITLE'             => 'Form appearance',    
    'NOVALNET_IFRAME_FIELD'                           => 'Form fields',
    'NOVALNET_IFRAME_LABEL'                           => 'Label',
    'NOVALNET_IFRAME_INPUT_FIELD'                     => 'Input field',
    'NOVALNET_IFRAME_STYLE_CONFIGURATION_TITLE'       => 'CSS settings for Credit Card iframe',    
    'NOVALNET_IFRAME_CSS'                             => 'CSS Text',
    'NOVALNET_BARZAHLEN'                              => 'Novalnet Barzahlen',
    'NOVALNET_BARZAHLEN_DUE_DATE_TITLE'               => 'Slip expiry date (in days)',
    'NOVALNET_BARZAHLEN_DUE_DATE_DESCRIPTION'         => 'Enter the number of days to pay the amount at store near you. If the field is empty, 14 days will be set as default',
    'NOVALNET_BARZAHLEN_DUE_DATE_UPDATE_TITLE'        => 'Change the amount / slip expiry date',
    'NOVALNET_BARZAHLEN_DUE_DATE_LABEL'               => 'Slip expiry date',
    'NOVALNET_BARZAHLEN_DUE_DATE'                     => '<br>Slip expiry date: ',
    'NOVALNET_BARZAHLEN_PAYMENT_STORE'                => '<br><br>Store(s) near you<br><br>',
    'NOVALNET_CONFIRM_SLIPDATE_UPDATE'                => 'Are you sure you want to change the order amount / slip expiry date?',
    'NOVALNET_INVALID_SLIPEDATE'                      => 'Please enter valid slip expiry date ',
    'NOVALNET_AMOUNT_DATE_UPDATED_MESSAGE'            => '<br><br><br>The transaction has been updated with amount %s and due date with %s',
    'NOVALNET_AMOUNT_SLIP_EXPIRY_DATE_UPDATED_MESSAGE' => '<br><br><br>The transaction has been updated with amount %s and slip expiry date with %s',
    'NOVALNET_ADMIN_MENU'                             => 'Novalnet updates',    
    'NOVALNET_ADMIN_UPDATE_VERSION'                   => '<h2><b>Novalnet Payment Module V11.4.2</b></h2>',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE'                       =>'Thank you for updating to the latest version of Novalnet Payment Modules. This version introduces some great new features and enhancements.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_IT'                    =>'We hope you enjoy it!',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_KEY'                   =>'Product Activation Key',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_KEY_DESC'              =>'Novalnet introduces Product Activation Key to fill entire merchant credentials automatically on entering the key into the Novalnet Global Configuration.To get the Product Activation Key',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_IP'                    =>'IP Address Configuration',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_IP_DESC'               =>'For all API access (Auto configuration with Product Activation Key, loading Credit Card iframe, Transaction API access, Transaction status enquiry, and update), it is required to configure a server IP address in Novalnet Merchant Administration portal.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_VENDOR_URL'            =>'Update of Vendor Script URL',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_VENDOR_URL_DESC'       =>'Vendor script URL is required to keep the merchant’s database/system up-to-date and synchronized with Novalnet transaction status. It is mandatory to configure the Vendor Script URL in Novalnet Merchant Administration portal.Novalnet system (via asynchronous) will transmit the information on each transaction and its status to the merchant’s system.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_ONE_CLICK'             =>'One Click Shopping',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_ONE_CLICK_DESC'        =>'Want your customers to make an order with a single click? With Novalnet payment module, they can! This feature can make the end customer to make order more conveniently with saved account/card details.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_ZERO_AMOUNT'           =>'Zero Amount Booking',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_ZERO_AMOUNT_DESC'      =>'Zero amount booking feature makes it possible for the merchant to sell variable amount product in the shop. Order will be processed with Zero amount initially, then the merchant can book the order amount later to complete the transaction.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_CC_IFRAME'             =>'Credit Card Responsive Iframe',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_CC_IFRAME_DESC'        =>'Now, we have updated the Credit Card with the most dynamic features. With the little bit of code, we have made the Credit Card iframe content responsive friendly.The merchant can customize the CSS settings of the Credit Card iframe form.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_KEY_DESCS'             =>'To get the Product Activation Key, please go to <a href=https://admin.novalnet.de target=_blank>Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Shop Parameters: API Signature (Product activation key).',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_IP_DESCS'              =>'To configure an IP address, go to <a href=https://admin.novalnet.de target=_blank>Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Project Overview: Payment Request IP\'s - Update Payment Request IP.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_VENDOR_URL_DESCS'      =>'To configure Vendor Script URL, go to <a href=https://admin.novalnet.de target=_blank>Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Project Overview - Vendor script URL.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_MORE'                  =>' But wait, there\'s more!',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_PAYPAL'                =>'Paypal',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_PAYPAL_DESE'           =>'To proceed transaction in PayPal payment, it is required to configure PayPal API details in Novalnet Merchant Administration portal.',
    'NOVALNET_ADMIN_CONFIG_PAYMENT_MODULE_UPDATE_PAYPAL_DESES'          =>'To configure Paypal API details, please go to <a href=https://admin.novalnet.de target=_blank>Novalnet Merchant Administration portal</a> - PROJECTS: Project Information - Payment Methods: Paypal - Configure.',
      
    'NOVALNET_PAYMENT_ACTION_TITLE'                                     => 'Payment action',    
    'NOVALNET_CONFIG_IP_ERROR1'         => 'You need to configure your outgoing server IP address ',
    'NOVALNET_CONFIG_IP_ERROR2'         =>' at Novalnet. Please configure it in Novalnet admin portal or contact technic@novalnet.de',
    'NOVALNET_IBAN'                                     => '<br>IBAN: ',
    'NOVALNET_BIC'                                      => '<br>BIC: ',
    'NOVALNET_BANK'                                     => '<br>Bank: ',
    'NOVALNET_PAYMENT_REFERENCE_1'                      => '<br>Payment Reference 1: ',
    'NOVALNET_PAYMENT_REFERENCE_2'                      => '<br>Payment Reference 2: ',
    'NOVALNET_ORDER_CONFIRMATION'                       => 'Order Confirmation - Your Order ',
    'NOVALNET_ORDER_CONFIRMATION1'                      => ' with ',
    'NOVALNET_ORDER_CONFIRMATION2'                      => ' has been confirmed',
    'NOVALNET_ORDER_CONFIRMATION3'                      => 'We are pleased to inform you that your order has been confirmed',
    'NOVALNET_PAYMENT_INFORMATION'                      => 'Payment Information:',
    'NOVALNET_PAYMENT_GUARANTEE_COMMENTS'               => 'This is processed as a guarantee payment<br>',
    'NOVALNET_PAYMENT_ACTION_CAPTURE'            => 'Capture',
    'NOVALNET_PAYMENT_ACTION_AUTHORIZE'          => 'Authorize',
];
?>
