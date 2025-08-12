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
 * Script: Order.php
 */

namespace oe\novalnet\Model;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Application\Model\UserPayment;
use OxidEsales\Eshop\Core\Counter;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Email;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;

/**
 * Class Order.
 */
class Order extends Order_parent
{
    /*
     * Get Novalnet paid date
     *
     * @var string
    */
    protected $sNovalnetPaidDate;

    /*
     * Get Novalnet data
     *
     * @var array
    */
    protected $aNovalnetData;

    /*
     * Get Novalnet reference
     *
     * @var string
    */
    protected $sInvoiceRef;

    /*
     * Get Novalnet session
     *
     * @var string
    */
    protected $oNovalnetSession;

    /**
     * Finalizes the order in shop
     *
     * @param Basket  $oBasket
     * @param User    $oUser
     * @param boolean $blRecalculatingOrder
     *
     * @return boolean
     */
    public function finalizeOrder(Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
        if (!preg_match("/novalnet/i", $oBasket->getPaymentId())) {
            return parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        } else {
            $aDynValue = Registry::getSession()->getVariable('dynvalue');
            if (empty($aDynValue)) {
                $aDynValue = Registry::getRequest()->getRequestParameter('dynvalue');
            }
            $aNovalnetPaymentDetails = html_entity_decode($aDynValue['novalnet_payment_details']);
            $aResponse = json_decode($aNovalnetPaymentDetails, true);
            $this->sNovalnetPaidDate = '0000-00-00 00:00:00';
            return $this->handleFinalizeOrderProcess($oBasket, $oUser, $blRecalculatingOrder, $aResponse);
        }
    }

    /**
     * Handle finalizes the order in shop
     *
     * @param Basket  $oBasket
     * @param User    $oUser
     * @param boolean $blRecalculatingOrder
     * @param object  $aResponse
     *
     * @return boolean
     */
    public function handleFinalizeOrderProcess($oBasket, $oUser, $blRecalculatingOrder, $aResponse)
    {
        $this->oNovalnetSession = Registry::getSession();
        $this->oDb  = DatabaseProvider::getDb();
        $this->oNovalnetSession->setVariable('sNovalnetPaymentName', $aResponse['payment_details']['name']);
        if (!empty($aResponse['booking_details']['payment_action'])) {
            $this->oNovalnetSession->setVariable('sNovalnetPaymentAction', $aResponse['booking_details']['payment_action']);
        }
        if (empty(NovalnetUtil::getRequestParameter('tid')) && empty(NovalnetUtil::getRequestParameter('status'))) {
            $sGetChallenge = $this->oNovalnetSession->getVariable('sess_challenge') ;
            if ($this->_checkOrderExist($sGetChallenge) && !$this->oNovalnetSession->getVariable('sRedirectPaymentProcessed')) {
                Registry::getUtils()->logger('BLOCKER');
                return self::ORDER_STATE_ORDEREXISTS;
            }
            if (!$blRecalculatingOrder) {
                $this->setId($sGetChallenge);
                if ($iOrderState = $this->validateOrder($oBasket, $oUser) && !$this->oNovalnetSession->getVariable('sRedirectPaymentProcessed')) {
                    return $iOrderState;
                }
            }
            $this->_setUser($oUser);
            $this->_loadFromBasket($oBasket);
            $oUserPayment = $this->_setPayment($oBasket->getPaymentId());
            $this->oNovalnetSession->setVariable('oUser', serialize($oUser));
            $this->oNovalnetSession->setVariable('oBasket', serialize($oBasket));
            $this->oNovalnetSession->setVariable('oUserPayment', serialize($oUserPayment));
            $this->oNovalnetSession->setVariable('oxidId', $this->oxorder__oxid->value);
            if (!$blRecalculatingOrder) {
                $this->_setFolder();
            }
            $this->_setOrderStatus('NOT_FINISHED');
            $blSave = $this->save();
            $this->oNovalnetSession->setVariable('blSave', $blSave);
        }
        if ((isset($aResponse['payment_details']['process_mode']) && $aResponse['payment_details']['process_mode'] == 'direct') || !empty(NovalnetUtil::getRequestParameter('tid'))) {
            $this->oNovalnetSession->deleteVariable('sRedirectPaymentProcessed');
            if (!$blRecalculatingOrder) {
                $oBasket =  unserialize($this->oNovalnetSession->getVariable('oBasket'));
                $oUser   = unserialize($this->oNovalnetSession->getVariable('oUser'));
                $oUserPayment = unserialize($this->oNovalnetSession->getVariable('oUserPayment'));
                $blRet = $this->_executePayment($oBasket, $oUserPayment);
                if ($blRet != true) {
                    $this->oNovalnetSession->deleteVariable('sess_challenge');
                    return $blRet;
                }
            }
        }

        if (isset($aResponse['payment_details']['process_mode']) && $aResponse['payment_details']['process_mode'] == 'redirect' && empty(NovalnetUtil::getRequestParameter('tid'))) {
            $this->oNovalnetSession->setVariable('sNovalnetProcessMode', $aResponse['payment_details']['process_mode']);
            $this->oNovalnetSession->setVariable('sPaymentId', $oBasket->getPaymentId());
            $this->oNovalnetSession->setVariable('sRedirectPaymentProcessed', true);
            $this->oNovalnetSession->deleteVariable('sess_challenge');
            // logs the transaction credentials
            $sPaymentName = !empty($this->oNovalnetSession->getVariable('sNovalnetPaymentName')) ? $this->oNovalnetSession->getVariable('sNovalnetPaymentName') : '';
            return NovalnetUtil::doPayment($this);
        }

        $this->aNovalnetDatas    = $this->oNovalnetSession->getVariable('aNovalnetGatewayResponse');

        if (empty($this->oxorder__oxordernr->value)) {
            $oDb  = DatabaseProvider::getDb();
            $iCnt = oxNew(Counter::class)->getNext($this->_getCounterIdent());
            $sQ = "update oxorder set oxordernr = :oxordernr where oxid = :oxid";
            $blUpdate = (bool) $oDb->execute($sQ, [
                ':oxordernr' => $iCnt,
                ':oxid' => $this->aNovalnetDatas['custom']['inputval2']
            ]);
            if ($blUpdate) {
                $this->oxorder__oxordernr = new \OxidEsales\Eshop\Core\Field($iCnt);
            }
        } else {
            oxNew(Counter::class)->update($this->_getCounterIdent(), $this->oxorder__oxordernr->value);
        }
        $this->oNovalnetSession->setVariable('dNnOrderNo', $this->oxorder__oxordernr->value);

        if ($this->oNovalnetSession->getVariable('sNovalnetProcessMode') == 'redirect') {
            $this->oNovalnetSession->deleteVariable('ordrem');
        }
        if (!$blRecalculatingOrder) {
            $sPaymentName = !empty($this->oNovalnetSession->getVariable('sNovalnetPaymentName')) ? $this->oNovalnetSession->getVariable('sNovalnetPaymentName') : '';
            $this->oDb->execute('INSERT INTO novalnet_transaction_detail (ORDER_NO, PAYMENT_TYPE) VALUES (?, ?)', [$this->oxorder__oxordernr->value, $sPaymentName]);
            $this->logNovalnetTransaction();
            NovalnetUtil::clearNovalnetSession();
        }
        $this->updateOrderNumber();
        $sOrderid = ($this->oNovalnetSession->getVariable('dNnOrderNo')) ? $this->oNovalnetSession->getVariable('dNnOrderNo') : $this->getId();
        $oBasket->setOrderId($sOrderid);
        $this->oNovalnetSession->setVariable('sess_challenge', $this->aNovalnetDatas['custom']['inputval2']);
        $this->_updateWishlist($oBasket->getContents(), $oUser);
        $this->_updateNoticeList($oBasket->getContents(), $oUser);
        if (!$blRecalculatingOrder) {
            $this->_updateOrderDate();
            $this->_markVouchers($oBasket, $oUser);
            if ($this->oNovalnetSession->getVariable('sNovalnetProcessMode') == 'redirect') {
                $sOrderId = $this->oNovalnetSession->getVariable('blSave');
                $oBasket = unserialize($this->oNovalnetSession->getVariable('oBasket'));
                $iRet = $this->nnSendOrderByEmail($sOrderId, $oBasket);
            } else {
                $iRet = $this->_sendOrderByEmail($oUser, $oBasket, $oUserPayment);
            }
        } else {
            $iRet = self::ORDER_STATE_OK;
        }
        NovalnetUtil::clearNovalnetRedirectSession();
        return $iRet;
    }

    protected function logNovalnetTransaction()
    {
        $iOrderNo               = !empty($this->oNovalnetSession->getVariable('dNnOrderNo')) ? $this->oNovalnetSession->getVariable('dNnOrderNo') : $this->oxorder__oxordernr->value;
        $aRequest               = $this->oNovalnetSession->getVariable('aNovalnetGatewayRequest');
        $this->aNovalnetData    = $this->oNovalnetSession->getVariable('aNovalnetGatewayResponse');
        $aNovalnetComments = $aStoreDetails = $aInvoiceDetails = [];
        $status = 'NOT_FINISHED';
        $this->aNovalnetData['test_mode'] = $aRequest['transaction']['test_mode'] == '1' ? $aRequest['transaction']['test_mode'] : $this->aNovalnetData['transaction']['test_mode'];
        if ($this->aNovalnetData['transaction']['status'] == 'CONFIRMED' && !in_array($this->aNovalnetData['transaction']['payment_type'], NovalnetUtil::$aUnPaidPayments)) {
            $dAmount = (in_array($this->aNovalnetData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA']) ? $this->aNovalnetData['instalment']['cycle_amount'] : $this->aNovalnetData['transaction']['amount']);
        } else {
            $dAmount = 0;
        }
        $aPaymentData = $aInstalmentDetails = [];
        if ($aRequest['transaction']['amount'] == '0' && !empty($this->oNovalnetSession->getVariable('sNovalnetPaymentAction')) && $this->oNovalnetSession->getVariable('sNovalnetPaymentAction') == 'zero_amount') {
            $aPaymentData['zero_request_data'] = $aRequest;
            $aPaymentData['zero_txn_reference'] = $this->aNovalnetData['transaction']['payment_data']['token'];
            $aPaymentData['zero_amount_booking'] = '1';
        }
        $aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->aNovalnetData['transaction']['tid']]];
        if (!empty($this->aNovalnetData['transaction']['test_mode'])) {
            $aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
        }
        if ($this->aNovalnetData['transaction']['status_code'] == '75' && in_array($this->aNovalnetData['transaction']['payment_type'], ['GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA','INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
            $aNovalnetComments[] = in_array($this->aNovalnetData['transaction']['payment_type'], ['GUARANTEED_INVOICE', 'INSTALMENT_INVOICE']) ? ['NOVALNET_INVOICE_PENDING_TEXT' => [null]] : ['NOVALNET_SEPA_PENDING_TEXT' => [null]];
        }
        if (
            !in_array($this->aNovalnetData['transaction']['payment_type'], NovalnetUtil::$aUnPaidPayments)
            && $this->aNovalnetData['transaction']['status'] == 'CONFIRMED' && $this->aNovalnetData['transaction']['amount'] != 0
        ) {
            $this->sNovalnetPaidDate  = NovalnetUtil::getFormatDateTime();
            $status = 'OK';
        }
        if ($this->aNovalnetData['transaction']['status'] == 'PENDING' && $this->aNovalnetData['transaction']['payment_type'] == 'INVOICE') {
            $status = 'OK';
        }

        if (in_array($this->aNovalnetData['transaction']['payment_type'], ['INVOICE', 'PREPAYMENT', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE']) && $this->aNovalnetData['transaction']['status_code'] != '75') {
            $aInvoiceDetails = NovalnetUtil::getInvoiceComments($this->aNovalnetData, $iOrderNo);
            $aPaymentData['bank_details'] = $aInvoiceDetails;
        } elseif ($this->aNovalnetData['transaction']['payment_type'] == 'CASHPAYMENT') {
            $aStoreDetails = NovalnetUtil::getStoreDetails($this->aNovalnetData);
            $aPaymentData['cp_checkout_token'] = $this->aNovalnetData['transaction']['checkout_token'];
            $aPaymentData['cp_checkout_js'] = $this->aNovalnetData['transaction']['checkout_js'];
        } elseif ($this->aNovalnetData['transaction']['payment_type'] == 'MULTIBANCO') {
            $amount = NovalnetUtil::formatCurrency($this->aNovalnetData['transaction']['amount'], $this->aNovalnetData['transaction']['currency']) . ' ' . $this->aNovalnetData['transaction']['currency'];
            $aNovalnetComments[] = ['NOVALNET_MULTIBANCO_TEXT' => [$amount]];
            $aNovalnetComments[] = ['NOVALNET_MULTIBANCO_PARTNER_REFERENCE' => [$this->aNovalnetData['transaction']['partner_payment_reference']]];
            if (!empty($this->aNovalnetData['transaction']['service_supplier_id'])) {
                $aNovalnetComments[] = ['NOVALNET_MULTIBANCO_PAYMENT_ENTITY_REFERENCE' => [$this->aNovalnetData['transaction']['service_supplier_id']]];
            }
        } elseif ($this->aNovalnetData['transaction']['payment_type'] == 'GOOGLEPAY') {
            $aNovalnetComments[] = ['NOVALNET_GOOGLEPAY_DESC' => [$this->aNovalnetData['transaction']['payment_data']['card_brand'], $this->aNovalnetData['transaction']['payment_data']['last_four']]];
        } elseif ($this->aNovalnetData['transaction']['payment_type'] == 'APPLEPAY') {
            $aNovalnetComments[] = ['NOVALNET_APPLEPAY_DESC' => [$this->aNovalnetData['transaction']['payment_data']['card_brand'], $this->aNovalnetData['transaction']['payment_data']['last_four']]];
        }

        if (($this->aNovalnetData['transaction']['amount'] == 0) && ($this->aNovalnetData['transaction']['status'] === 'CONFIRMED') && (isset($this->aNovalnetData['custom']) && isset($this->aNovalnetData['custom']['input4']) && $this->aNovalnetData['custom']['input4'] == 'ZeroBooking')) {
            $aNovalnetComments[] = ['NOVALNET_ZERO_BOOKING_TEXT' => [null]];
        }

        $aComments = array_merge($aNovalnetComments, $aStoreDetails, $aInvoiceDetails);
        $aPaymentData['novalnet_comments'][] = $aComments;

        if (
            in_array($this->aNovalnetData['transaction']['payment_type'], array('INSTALMENT_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE'))
            && $this->aNovalnetData['transaction']['status'] == 'CONFIRMED'
        ) {
            $aInstalmentDetails = NovalnetUtil::formInstalmentData($this->aNovalnetData);
            $aPaymentData['instalment_comments'] = $aInstalmentDetails;
        }
        NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $this->sNovalnetPaidDate, 'OXTRANSSTATUS' => $status], 'OXORDERNR', $iOrderNo);
        $this->oxorder__oxpaid           = new Field($this->sNovalnetPaidDate);
        $this->oxorder__oxtransstatus    = new Field($status);
        if (preg_match('/INSTALMENT/', $this->aNovalnetData['transaction']['payment_type'])) {
            $totalAmount = $this->aNovalnetData['instalment']['total_amount'];
        } else {
            $totalAmount = $this->aNovalnetData['transaction']['amount'];
        }
        // logs the transaction credentials, status and amount details
        NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['TID' => $this->aNovalnetData['transaction']['tid'], 'AMOUNT' => $totalAmount, 'GATEWAY_STATUS' => $this->aNovalnetData['transaction']['status'],  'CREDITED_AMOUNT' => $dAmount, 'ADDITIONAL_DATA' => json_encode($aPaymentData)], 'ORDER_NO', $iOrderNo);
    }

    /**
     * Send order mail
     *
     * @param string $sOrderId
     * @param object $oBasketValue
     *
     * @return boolean
     */
    protected function nnSendOrderByEmail($sOrderId, $oBasketValue)
    {
        $oOrder = oxNew(Order::class);
        $oOrder->load($sOrderId);
        $oUser  = oxNew(User::class);
        $oUser->load($oOrder->oxorder__oxuserid->value);
        $oOrder->_oUser = $oUser;
        $oPayment = oxNew(UserPayment::class);
        $oPayment->load($oOrder->oxorder__oxpaymentid->value);
        $oOrder->_oPayment = $oPayment;
        $oOrder->_oBasket = $oBasketValue;
        $oxEmail = oxNew(Email::class);
        // Send order email to user
        if ($oxEmail->sendOrderEMailToUser($oOrder)) {
            $iRet = self::ORDER_STATE_OK;
        } else {
            $iRet = self::ORDER_STATE_MAILINGERROR;
        }
        // Send order email to shop owner
        $oxEmail->sendOrderEMailToOwner($oOrder);
        return $iRet;
    }

    /**
     * Send the postback call to the Novalnet server to update order number and Invoice ref.
     *
     * @return null
     */
    protected function updateOrderNumber()
    {
        $aTransactionDetails = [
                'transaction' => [
                    'tid'           => $this->aNovalnetData['transaction']['tid'],
                    'order_no'      => $this->oxorder__oxordernr->value,
                    'invoice_ref'   => $this->sInvoiceRef
                ]
            ];
        NovalnetUtil::doCurlRequest($aTransactionDetails, 'transaction/update');
    }

    /**
     * Loads the order model
     *
     * @param  string $oOxid
     * @return bool
     */
    public function load($oOxid)
    {
        parent::load($oOxid);
        if ($this->oxorder__oxpaymenttype->value == 'novalnetpayments') {
            $orderId = NovalnetUtil::getTableValues('OXORDERNR', 'oxorder', 'OXID', $oOxid);
            $sPaymentName = NovalnetUtil::getTableValues('PAYMENT_TYPE', 'novalnet_transaction_detail', 'ORDER_NO', $orderId['OXORDERNR']);
            if (!empty($sPaymentName) && !empty($sPaymentName['PAYMENT_TYPE'])) {
                $this->oxorder__oxpaymenttype->rawValue = 'NOVALNET_' . $sPaymentName['PAYMENT_TYPE'];
                $this->oxorder__oxpaymenttype->value = 'NOVALNET_' . $sPaymentName['PAYMENT_TYPE'];
            }
        }

        return $this->_isLoaded;
    }
}
