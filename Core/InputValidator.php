<?php

/**
 * Novalnet payment module
 *
 * This file is used for validating the Novalnet payment request data
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: InputValidator.php
 */

namespace oe\novalnet\Core;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class InputValidator.
 */
class InputValidator extends InputValidator_parent
{
    /**
     * Validates payments input data from payment page
     *
     * @param string $sPaymentId
     * @param array  &$aDynValue
     *
     * @return boolean
     */
    public function validatePaymentInputData($sPaymentId, &$aDynValue)
    {
        $oNovalnetOxUtils = Registry::getUtils();
        if ($sPaymentId == 'novalnetpayments' && !empty($aDynValue['novalnet_payment_error'])) {
            $oNovalnetOxUtils->redirect(NovalnetUtil::setRedirectURL($aDynValue['novalnet_payment_error']));
        } else {
            parent::validatePaymentInputData($sPaymentId, $aDynValue);
        }
    }
}
