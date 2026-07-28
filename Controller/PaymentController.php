<?php

/**
 * Novalnet payment module
 *
 * This file is used for forming the payment link by sending
 * payby link API to server.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: PaymentController.php
 */

namespace oe\novalnet\Controller;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class PaymentController.
 */
class PaymentController extends PaymentController_parent
{
    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        return parent::render();
    }

    /**
     * Get payment to show on the payment page
     *
     * @return array
     */
    public function getPaymentList()
    {
        parent::getPaymentList();
        foreach ($this->_oPaymentList as $oPayment) {
            $sCurrentPayment = $oPayment->oxpayments__oxid->value;
            // Checks the payment is Novalnet or not
            if (NovalnetUtil::CheckNovalnetPayment($sCurrentPayment)) {
                if ($this->validateNovalnetConfig() === false) {
                    // Hides the payment on checkout page if Novalnet configuration values empty
                    unset($this->_oPaymentList[$sCurrentPayment]);
                }
            }
        }
        return $this->_oPaymentList;
    }

    /**
     * Validate configuration values
     *
     * @return boolean
     */
    public function validateNovalnetConfig()
    {
        $sProcessActivationKey = NovalnetUtil::getNovalnetConfigValue('sProductActivationKey');
        $sTariffId = NovalnetUtil::getNovalnetConfigValue('sTariffId');
        $sAccessKey = NovalnetUtil::getNovalnetConfigValue('sPaymentAccessKey');
        // Validate configuration values
        if (empty($sProcessActivationKey) || empty($sAccessKey) || !NovalnetUtil::isValidDigit($sTariffId)) {
            return false;
        }
        return true;
    }

    /**
     * Get the redirect url
     *
     * @return mixed
     */
    public function getNovalnetPayByLink($shippingcost)
    {
        $oBasket = Registry::getSession()->getBasket();
        $oUser = $this->getUser();
        $aRequest = [];
        $aRequest['merchant'] = [
            'signature' => NovalnetUtil::getNovalnetConfigValue('sProductActivationKey'),
            'tariff'    => NovalnetUtil::getNovalnetConfigValue('sTariffId')
        ];
        $aRequest['customer'] = NovalnetUtil::getCustomerData($oUser);
        $aRequest['transaction'] = NovalnetUtil::getTransactionData($oBasket, $shippingcost);
        $aRequest['hosted_page'] = [
            'type' => 'PAYMENTFORM',
        ];
        $aRequest['custom']['lang'] = strtoupper(\OxidEsales\Eshop\Core\Registry::getLang()->getLanguageAbbr());
        $aResponse = NovalnetUtil::doCurlRequest($aRequest, 'seamless/payment');

        if (!empty($aResponse['result']['status']) && $aResponse['result']['status'] == 'SUCCESS') {
            return $aResponse['result']['redirect_url'];
        } else {
            return null;
        }
    }
}
