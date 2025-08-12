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
 * Script: Payment.php
 */

namespace oe\novalnet\Model;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Payment.
 */
class Payment extends Payment_parent
{
    /**
     * Loads payment model
     *
     * @param  $id
     * @return mixed
     */
    public function load($id)
    {
        parent::load($id);
        if (NovalnetUtil::checkNovalnetPayment($id)) {
            $aDynValue = Registry::getSession()->getVariable('dynvalue');
            if (!empty($aDynValue) && !empty($aDynValue['novalnet_payment_details'])) {
                $aNovalnetPaymentDetails = html_entity_decode($aDynValue['novalnet_payment_details']);
                $aResponse = json_decode($aNovalnetPaymentDetails, true);
                if (!empty($aResponse['payment_details']['name'])) {
                    $this->oxpayments__oxdesc->rawValue = $aResponse['payment_details']['name'];
                }
            } elseif (preg_match("/novalnet_/i", $id)) {
                $aIndex = explode("_", $id);
                if (!empty($aIndex)) {
                    $this->oxpayments__oxdesc->rawValue = $aIndex[1];
                    $this->oxpayments__oxdesc->value = $aIndex[1];
                }
            }
        }
        return $this->_isLoaded;
    }
}
