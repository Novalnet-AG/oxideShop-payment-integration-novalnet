<?php

/**
 * Novalnet payment module
 *
 * This file is used for real time processing of transaction.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: NovalnetThankyou.php
 */

namespace oe\novalnet\Controller;

class NovalnetThankyou extends NovalnetThankyou_parent
{
    /**
     * Order object
     *
     * @var object
     */
    public $_oOrder = null;

    public function render()
    {
        $sTemplate = parent::render();
        $oOrder     = $this->getOrder();
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
        $iOrderNr = $oOrder->oxorder__oxordernr->value;
        if (preg_match("/novalnet/i", $oOrder->oxorder__oxpaymenttype->value)) {
            $sSql = 'SELECT ADDITIONAL_DATA FROM novalnet_transaction_detail where ORDER_NO = "' . $iOrderNr . '"';
            $aPaymentRef = $oDb->getRow($sSql);
            $sData = json_decode($aPaymentRef['ADDITIONAL_DATA'], true);
            if (!empty($sData['cp_checkout_token']) && !empty($sData['cp_checkout_js'])) {
                $this->_aViewData['aNovalnetBarzahlensUrl'] = $sData['cp_checkout_js'];
                $this->_aViewData['aNovalnetToken'] = $sData['cp_checkout_token'];
            }
        }
        return $sTemplate;
    }
}
