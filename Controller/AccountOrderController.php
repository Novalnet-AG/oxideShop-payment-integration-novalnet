<?php

/**
 * Novalnet payment module
 *
 * This file is used for displaying Novalnet details in the account page
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: AccountOrderController.php
 */

namespace oe\novalnet\Controller;

use oe\novalnet\Core\NovalnetUtil;

/**
 * Class AccountOrderController.
 */
class AccountOrderController extends AccountOrderController_parent
{
    /**
     * Get Novalnet payment method
     *
     * @param  int    $iOrderNo
     * @param  string $sPaymentMethod
     * @return array $aInstalment
     */
    public function getNovalnetPayment($iOrderNo, $sPaymentMethod)
    {
        $aNovalnetOrder = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $iOrderNo);
        //Update old txn details to New format
        if (!empty($aNovalnetOrder) && $sPaymentMethod != 'novalnetpayments' && !preg_match("/novalnet_/i", $sPaymentMethod)) {
            $aAdditionalData = unserialize($aNovalnetOrder['ADDITIONAL_DATA']);
            if (empty($aAdditionalData)) {
                $aAdditionalData = json_decode($aNovalnetOrder['ADDITIONAL_DATA'], true);
            }
            if (isset($aAdditionalData['updated_old_txn_details']) && $aAdditionalData['updated_old_txn_details'] = true) {
                return $aNovalnetOrder['PAYMENT_TYPE'];
            } else {
                NovalnetUtil::convertOldTxnDetailsToNewFormat($iOrderNo);
                $aNovalnetPayment = NovalnetUtil::getTableValues('PAYMENT_TYPE', 'novalnet_transaction_detail', 'ORDER_NO', $iOrderNo);
                return $aNovalnetPayment['PAYMENT_TYPE'];
            }
        } elseif ($sPaymentMethod == 'novalnetpayments') {
            return $aNovalnetOrder['PAYMENT_TYPE'];
        }
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
     * Get Novalnet Instalment Details
     *
     * @param int $iOrderNo
     *
     * @return array
     */
    public function getNovalnetInstalmentComments($iOrderNo)
    {
        $aInstalmentData = NovalnetUtil::getAdditionalData($iOrderNo);
        $aInstalment = [];
        if (!empty($aInstalmentData['instalment_comments'])) {
            $aData = $aInstalmentData['instalment_comments'];
            $aInstalmentCycleList = [
                'cycles' => []
            ];

            if (!empty($aData)) {
                for ($dCycleCount = 1; $dCycleCount <= $aData['instalment_total_cycles']; $dCycleCount++) {
                    array_push($aInstalmentCycleList['cycles'], $dCycleCount);
                }
                foreach ($aInstalmentCycleList['cycles'] as $dCurrentInstalmentCycle => $dCurrentInstalmentData) {
                    foreach ($aData as $dInstalmentCycle => $dInstalmentData) {
                        if ($dInstalmentCycle == 'instalment' . $dCurrentInstalmentData) {
                            $aInstalment[$dCurrentInstalmentData] = $aData[$dInstalmentCycle];
                        }
                    }
                }
            }
        }
        return $aInstalment;
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
}
