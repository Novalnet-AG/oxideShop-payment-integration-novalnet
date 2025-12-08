<?php

/**
 * Novalnet payment module
 *
 * This file is used for executing the payment transaction.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: PaymentGateway.php
 */

namespace oe\novalnet\Model;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Class PaymentGateway.
 */
class PaymentGateway extends PaymentGateway_parent
{
    /**
     * @var string
     */
    protected $_sLastError;

    /**
     * Executes payment, returns true on success.
     *
     * @param double $dAmount
     * @param object &$oOrder
     *
     * @return boolean
     */
    public function executePayment($dAmount, &$oOrder)
    {
        // Check the current payment method is not a Novalnet payment. If yes, then skips the execution of this function
        $oSession = Registry::getSession();
        if ((!empty($oOrder->oxorder__oxpaymenttype->value) && !preg_match("/novalnet/i", $oOrder->oxorder__oxpaymenttype->value)) || (!empty($oSession->getVariable('sPaymentId')) && !preg_match("/novalnet/i", $oSession->getVariable('sPaymentId')))) {
            return parent::executePayment($dAmount, $oOrder);
        }

        if (NovalnetUtil::getRequestParameter('tid') && NovalnetUtil::getRequestParameter('status')) {
            // check if the tid is already exits in database
            $aOrderNo  = NovalnetUtil::getTableValues('ORDER_NO', 'novalnet_transaction_detail', 'TID', NovalnetUtil::getRequestParameter('tid'));
            if (!empty($aOrderNo)) {
                return parent::executePayment($dAmount, $oOrder);
            }
            // Check to validate the redirect response
            if ($this->validateNovalnetRedirectResponse() === false) {
                return false;
            }
        } else {
            NovalnetUtil::doPayment($oOrder);
            return true;
        }
        return true;
    }

    /**
     * Validates Novalnet redirect payment response
     *
     * @return boolean
     */
    protected function validateNovalnetRedirectResponse()
    {
        $aNovalnetResponse = $_REQUEST;
        $oSession = Registry::getSession();
        $oLang = Registry::getLang();
        $sNovalnetTxnSecret = $oSession->getVariable('sNovalnetTxnSecret');
        if (!empty($aNovalnetResponse['status']) && $aNovalnetResponse['status'] == 'SUCCESS') {
            if (!empty($aNovalnetResponse['checksum']) && !empty($aNovalnetResponse['tid']) && !empty($sNovalnetTxnSecret) && !empty($aNovalnetResponse['status'])) {
                $token_string = $aNovalnetResponse['tid'] . $sNovalnetTxnSecret . $aNovalnetResponse['status'] . strrev(NovalnetUtil::getNovalnetConfigValue('sPaymentAccessKey'));

                $mGeneratedChecksum = hash('sha256', $token_string);

                if ($mGeneratedChecksum !== $aNovalnetResponse['checksum']) {
                    $this->_sLastError = $oLang->translateString('NOVALNET_CHECK_HASH_FAILED_ERROR');
                    return false;
                } else {
                    // Handle further process here for the successful scenario
                    $aTransactionDetails = ['transaction' => ['tid' => $aNovalnetResponse['tid']]];
                    $aResponse = NovalnetUtil::doCurlRequest($aTransactionDetails, 'transaction/details');
                    $oSession->setVariable('aNovalnetGatewayResponse', $aResponse);
                }
            }
        } else {
            $aTransactionDetails = ['transaction' => ['tid' => $aNovalnetResponse['tid']], 'custom' => ['lang' => strtoupper(Registry::getLang()->getLanguageAbbr())]];
            $aResponse = NovalnetUtil::doCurlRequest($aTransactionDetails, 'transaction/details');
            $oOrdrId  = $aResponse['custom']['inputval2'];
            $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
            $oOrder->delete($oOrdrId);
            if (!empty($oSession->getVariable('sess_challenge'))) {
                $oSession->deleteVariable('sess_challenge');
            }
            NovalnetUtil::updateArticleStockFailureOrder($oOrdrId);
            NovalnetUtil::clearNovalnetRedirectSession();
            NovalnetUtil::setNovalnetPaygateError($aResponse['result']);
        }
        return true;
    }
}
