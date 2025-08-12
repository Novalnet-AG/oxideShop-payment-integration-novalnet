<?php

/**
 * Novalnet payment module
 *
 * This file is used for receiving Novalnet response for
 * the redirect payments.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: OrderController.php
 */

namespace oe\novalnet\Controller;

use OxidEsales\Eshop\Core\Registry;

/**
 * Class OrderController.
 */
class OrderController extends OrderController_parent
{
    /**
     * Receives Novalnet response for redirect payment
     *
     * @return boolean
     */
    public function novalnetGatewayReturn()
    {
        $oConfig = Registry::getConfig();
        if (!$oConfig->getRequestParameter('ord_agb') && $oConfig->getConfigParam('blConfirmAGB')) {
            $_POST['ord_agb'] = 1;
        }

        if ($oConfig->getConfigParam('blEnableIntangibleProdAgreement')) {
            if (!$oConfig->getRequestParameter('oxdownloadableproductsagreement')) {
                $_POST['oxdownloadableproductsagreement'] = 1;
            }

            if (!$oConfig->getRequestParameter('oxserviceproductsagreement')) {
                $_POST['oxserviceproductsagreement'] = 1;
            }
        }

        Registry::getLang()->setBaseLanguage($oConfig->getRequestParameter('shop_lang'));
        return $this->execute();
    }
}
