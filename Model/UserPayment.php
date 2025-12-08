<?php

/**
 * Novalnet payment module
 *
 * This file is used for processing the order for the payments
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: UserPayment.php
 */

namespace oe\novalnet\Model;

use oe\novalnet\Core\NovalnetUtil;

/**
 * Class UserPayment.
 */
class UserPayment extends UserPayment_parent
{
    /**
     * Loads the User Payment
     *
     * @param  $oOxid
     * @return mixed
     */
    public function load($oOxid)
    {
        $opayment = parent::load($oOxid);
        if (!empty($oOxid)) {
            $aOrder = NovalnetUtil::getTableValues('OXORDERNR,OXPAYMENTTYPE', 'oxorder', 'OXPAYMENTID', $oOxid);
            if (!empty($aOrder['OXPAYMENTTYPE']) && $aOrder['OXPAYMENTTYPE'] == 'novalnetpayments') {
                $sPaymentName = NovalnetUtil::getTableValues('PAYMENT_TYPE', 'novalnet_transaction_detail', 'ORDER_NO', $aOrder['OXORDERNR']);
                if (!empty($sPaymentName) && !empty($sPaymentName['PAYMENT_TYPE']) && !empty($this->oxpayments__oxdesc)) {
                    $this->oxpayments__oxdesc->rawValue = $sPaymentName['PAYMENT_TYPE'];
                    $this->oxpayments__oxdesc->value = $sPaymentName['PAYMENT_TYPE'];
                }
            }
        }
        return $opayment;
    }
}
