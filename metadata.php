<?php

/**
 * Novalnet payment module
 *
 * This file is used for metadata information of Novalnet payment module.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: metadata.php
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
        'id'          => 'novalnet',
        'title'       => [
            'de' => 'Novalnet',
            'en' => 'Novalnet',
        ],
        'description' => [ 'de' => 'Bevor Sie beginnen, lesen Sie bitte die Installationsanleitung und melden Sie sich mit Ihrem Händlerkonto im <a href ="https://admin.novalnet.de" target="_blank" style="color: #777;text-decoration: none;
    border-bottom: 1px dotted #999;">Novalnet Admin-Portal</a> an. Um ein Händlerkonto zu erhalten, senden Sie bitte eine E-Mail an sales@novalnet.de oder rufen Sie uns unter +49 89 923068320 an' . '<br><br>' . 'Die Konfigurationen der Zahlungsplugins sind jetzt im <a href ="https://admin.novalnet.de" target="_blank" style="color: #777;text-decoration: none;
    border-bottom: 1px dotted #999;">Novalnet Admin-Portal</a> verfügbar. Navigieren Sie zu Projekte -> Wählen Sie Ihr Projekt -> Konfiguration des Zahlungsplugins des Shops Ihrer Projekte, um sie zu konfigurieren.' . '<br><br>' . 'Novalnet ermöglicht es Ihnen, das Verhalten der Zahlungsmethode zu überprüfen, bevor Sie in den Produktionsmodus gehen, indem Sie Testzahlungsdaten verwenden. Zugang zu den <a href ="https://developer.novalnet.de/testing" target="_blank" style="color: #777;text-decoration: none;border-bottom: 1px dotted #999;">Novalnet-Testzahlungsdaten</a> finden Sie hier',
                       'en' => 'Please read the Installation Guide before you start and login to the <a href ="https://admin.novalnet.de" target="_blank" style="color: #777;text-decoration: none;border-bottom: 1px dotted #999;">Novalnet Admin Portal</a> using your merchant account. To get a merchant account, mail to sales@novalnet.de or call +49 (089) 923068320' . '<br><br>' . 'Payment plugin configurations are now available in the <a href ="https://admin.novalnet.de" target="_blank" style="color: #777;text-decoration: none;border-bottom: 1px dotted #999;">Novalnet Admin Portal</a>. Navigate to the Projects -> choose your project -> Payment plugin configuration of your projects to configure them.' . '<br><br>' . 'Our platform offers a test mode for all requests; You can control the behaviour of the payment methods by using the <a href ="https://developer.novalnet.com/testing" target="_blank" style="color: #777;text-decoration: none;border-bottom: 1px dotted #999;">Novalnet test payment data</a>',
        ],
        'thumbnail'   => 'icon.png',
        'version'     => '13.3.1',
        'author'      => 'Novalnet AG',
        'url'         => 'https://www.novalnet.de',
        'email'       => 'technic@novalnet.de',
        'extend'      => [
            \OxidEsales\Eshop\Application\Controller\PaymentController::class        => \oe\novalnet\Controller\PaymentController::class,
            \OxidEsales\Eshop\Core\InputValidator::class                             => \oe\novalnet\Core\InputValidator::class,
            \OxidEsales\Eshop\Application\Model\PaymentGateway::class                => \oe\novalnet\Model\PaymentGateway::class,
            \OxidEsales\Eshop\Application\Model\Order::class                         => \oe\novalnet\Model\Order::class,
            \OxidEsales\Eshop\Application\Model\Payment::class                       => \oe\novalnet\Model\Payment::class,
            \OxidEsales\Eshop\Application\Model\UserPayment::class                   => \oe\novalnet\Model\UserPayment::class,
            \OxidEsales\Eshop\Application\Controller\AccountOrderController::class   => \oe\novalnet\Controller\AccountOrderController::class,
            \OxidEsales\Eshop\Application\Controller\OrderController::class          => \oe\novalnet\Controller\OrderController::class,
            \OxidEsales\Eshop\Core\ViewConfig::class                                 => \oe\novalnet\Core\ViewConfig::class,
            \OxidEsales\Eshop\Application\Controller\ThankYouController::class       => \oe\novalnet\Controller\NovalnetThankyou::class,
            \OxidEsales\Eshop\Application\Controller\Admin\OrderList::class          => \oe\novalnet\Controller\Admin\OrderListController::class,

        ],
        'controllers'  => [
            'novalnetconfiguration'         => \oe\novalnet\Controller\Admin\NovalnetConfiguration::class,
            'novalnetcallback'              => \oe\novalnet\Controller\CallbackController::class,
            'novalnet_order'                => \oe\novalnet\Controller\Admin\OrderController::class,
        ],
        'templates'   => [
            'novalnet_callback.tpl'     => 'oe/novalnet/views/tpl/novalnet_callback.tpl',
            'novalnet_order.tpl'        => 'oe/novalnet/views/admin/tpl/novalnet_order.tpl',
        ],
        'blocks'      => [
            [   'template' => 'module_config.tpl',
                'block'    => 'admin_module_config_var_type_str',
                'file'     => '/views/admin/blocks/novalnet_config_str.tpl'
            ],
            [   'template' => 'module_config.tpl',
                'block'    => 'admin_module_config_var_type_bool',
                'file'     => '/views/admin/blocks/novalnet_config_bool.tpl'
            ],
            [   'template' => 'module_config.tpl',
                'block'    => 'admin_module_config_var_type_select',
                'file'     => '/views/admin/blocks/novalnet_config_select.tpl'
            ],
            [   'template' => 'module_config.tpl',
                'block'    => 'admin_module_config_form',
                'file'     => '/views/admin/blocks/novalnet_config_webhook_button.tpl'
            ],
            [   'template' => 'page/checkout/payment.tpl',
                'block'    => 'select_payment',
                'file'     => '/views/blocks/page/checkout/novalnet_payments.tpl'
            ],
            [   'template' => 'page/account/order.tpl',
                'block'    => 'account_order_history',
                'file'     => '/views/blocks/page/account/novalnet_order.tpl'
            ],
            [   'theme'    => 'flow',
                'template' => 'page/account/order.tpl',
                'block'    => 'account_order_history',
                'file'     => '/views/blocks/page/account/novalnet_order_flow.tpl'
            ],
            [  'theme'     => 'azure',
                'template' => 'page/account/order.tpl',
                'block'    => 'account_order_history',
                'file'     => '/views/blocks/page/account/novalnet_order_azure.tpl'
            ],
            [   'theme'    => 'wave',
                'template' => 'page/account/order.tpl',
                'block'    => 'account_order_history',
                'file'     => '/views/blocks/page/account/novalnet_order_wave.tpl'
            ],
            [   'template' => 'page/checkout/payment.tpl',
                'block'    => 'checkout_payment_errors',
                'file'     => '/views/blocks/page/checkout/novalnet_payment_errors.tpl',
            ],
            [   'template' => 'email/html/order_cust.tpl',
                'block'    => 'email_html_order_cust_username',
                'file'     => '/views/blocks/email/html/novalnet_transaction.tpl'
            ],
            [   'template' => 'email/html/order_owner.tpl',
                'block'    => 'email_html_order_owner_username',
                'file'     => '/views/blocks/email/html/novalnet_transaction.tpl'
            ],
            [   'template' => 'email/plain/order_cust.tpl',
                'block'    => 'email_plain_order_cust_username',
                'file'     => '/views/blocks/email/html/novalnet_transaction.tpl'
            ],
            [   'template' => 'email/plain/order_owner.tpl',
                'block'    => 'email_plain_order_ownerusername',
                'file'     => '/views/blocks/email/html/novalnet_transaction.tpl'
            ],
            [   'template' => 'page/checkout/thankyou.tpl',
                'block'    => 'checkout_thankyou_proceed',
                'file'     => '/views/blocks/page/checkout/novalnet_thankyou.tpl'
            ],
        ],
        'settings'      => [
            // Global configuration settings
            ['group' => 'novalnetGlobalSettings', 'name' => 'sProductActivationKey','type' => 'str',   'value'  => '', 'position' => 1 ],
            ['group' => 'novalnetGlobalSettings', 'name' => 'sPaymentAccessKey',    'type' => 'str',   'value'  => '', 'position' => 2 ],
            ['group' => 'novalnetGlobalSettings','name'  => 'sTariffId',            'type' => 'select','value'  => '', 'position' => 3],
            ['group' => 'novalnetGlobalSettingsWebhook','name'  => 'sWebhooksUrl',         'type' => 'str',    'value' => '',      'position' => 4],
            ['group' => 'novalnetGlobalSettingsWebhook','name'  => 'blWebhookNotification','type' => 'bool',   'value' => 'false', 'position' => 5],
            ['group' => 'novalnetGlobalSettingsWebhook','name'  => 'blWebhookSendMail',    'type' => 'str',    'value' => '',      'position' => 6],
        ],
        'events'    => [
            'onActivate'    => '\oe\novalnet\Core\Events::onActivate',
            'onDeactivate'  => '\oe\novalnet\Core\Events::onDeactivate'
        ],
];
