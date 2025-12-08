<?php

/**
 * Novalnet payment module
 *
 * This file is used for assigning the view values.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: ViewConfig.php
 */

namespace oe\novalnet\Core;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class ViewConfig.
 */
class ViewConfig extends ViewConfig_parent
{
    /**
     * Get shop admin URL
     *
     * @return string
     */
    public function getNovalnetShopUrl()
    {
        $oConfig = \OxidEsales\Eshop\Core\Registry::getConfig();
        // override cause of admin dir
        $sURL = $oConfig->getConfigParam('sShopURL') . $oConfig->getConfigParam('sAdminDir') . "/";

        if ($oConfig->getConfigParam('sAdminSSLURL')) {
            $sURL = $oConfig->getConfigParam('sAdminSSLURL');
        }

        return $sURL;
    }

    /**
     * Get Novalnet Comments
     *
     * @param int $iOrderNo
     *
     * @return array
     */
    public function getNovalnetOrderComments($iOrderNo)
    {
        return NovalnetUtil::getNovalnetTransactionComments($iOrderNo);
    }

    /**
     * Get shop's current theme
     *
     * @return string
     */
    public function getShopTheme()
    {
        $oTheme = oxNew('oxTheme');
        return $oTheme->getActiveThemeId();
    }

    /**
     * Get instalment amount
     *
     * @return integer
     */
    public function getNovalnetInstalmentAmount($dAmount)
    {
        return str_replace(',', '.', $dAmount) * 100;
    }

    /**
     * Get template folder path
     *
     * @param string $sFile
     *
     * @return string
     */
    public function getPaymentTemplatePath($sFile = "")
    {
        $viewConfig = oxNew(\OxidEsales\Eshop\Core\ViewConfig::class);
        $sModulePath = $viewConfig->getModulePath('novalnet');
        $sModulePath = $sModulePath . 'views/blocks/';
        if ($sFile) {
            return $sModulePath . $sFile;
        }
    }

    /**
     * Returns shop url
     *
     * @return string
     */
    public function getShopUrl()
    {
        return rtrim(Registry::getConfig()->getSslShopUrl(), '/') . '/';
    }

    /**
     * Get order details for Google pay and apple pay
     *
     * @param object $oBasket
     * @param float  $shippingcost
     *
     * @return object
     */
    public function getOrderDetails($oBasket, $shippingcost)
    {
        $articleDetails = NovalnetUtil::getBasketDetails($oBasket, $shippingcost);
        return json_encode($articleDetails);
    }

    /**
     * Fetches current time stamp
     *
     * @return int
     */
    public function getTimeStamp()
    {
        return time();
    }
}
