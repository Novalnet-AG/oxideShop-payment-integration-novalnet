<?php

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
 * Script: NovalnetConfiguration.php
 */

namespace oe\novalnet\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ShopConfiguration;
use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class NovalnetConfiguration.
 */
class NovalnetConfiguration extends ShopConfiguration
{
    /**
     * Get merchant details from Novalnet
     *
     * @return null
     */
    public function getMerchantDetails()
    {
        $sAccessKey = NovalnetUtil::getRequestParameter('accessKey');
        $sHash      = NovalnetUtil::getRequestParameter('hash');
        if (!empty($sAccessKey) && !empty($sHash)) {
            $aData = [
                'merchant' => [
                    'signature' => $sHash,
                ],
                'custom'   => [
                    'lang' => Registry::getLang()->getLanguageAbbr(),
                ],
            ];
            $aResponse = NovalnetUtil::doCurlRequest($aData, 'merchant/details', $sAccessKey);
            if (!empty($aResponse['result']['status'])) {
                if ($aResponse['result']['status'] == 'SUCCESS') {
                    echo json_encode(['details' => 'true','response' => $aResponse]);
                } else {
                    echo json_encode(['error' => $aResponse['result']['status_text']]);
                }
                exit();
            }
        }
    }

    /**
     * Update Webhook URL at Novalnet
     *
     * @return null
     */
    public function updateWebhookUrl()
    {
        $sAccessKey = NovalnetUtil::getRequestParameter('accessKey');
        $sWebhookUrl = NovalnetUtil::getRequestParameter('webhookUrl');
        $sSignature = NovalnetUtil::getRequestParameter('activationKey');
        if (! empty($sAccessKey) && ! empty($sWebhookUrl) && ! empty($sSignature)) {
            $aData = [
                'merchant' => [
                    'signature' => $sSignature,
                ],
                'webhook' => [
                    'url' => $sWebhookUrl,
                ],
                'custom'   => [
                    'lang' => Registry::getLang()->getLanguageAbbr(),
                ],
            ];
            $aResponse = NovalnetUtil::doCurlRequest($aData, 'webhook/configure', $sAccessKey);

            if (!empty($aResponse['result']['status'])) {
                if ($aResponse['result']['status'] == 'SUCCESS') {
                    Registry::getConfig()->saveShopConfVar('str', 'sWebhooksUrl', $sWebhookUrl, '', 'novalnet');
                    echo json_encode(['details' => 'true','response' => $aResponse]);
                } else {
                    echo json_encode(['error' => $aResponse['result']['status_text']]);
                }
                exit();
            }
        }
    }
}
