<?php

/**
 * Novalnet payment module
 *
 * This file is used for proceeding the post process API from the shop admin
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: OrderController.php
 */

namespace oe\novalnet\Controller\Admin;

use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;

/**
 * Class OrderController.
 */
class OrderController extends AdminDetailsController
{
    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        parent::render();
        $sOxId = $this->getEditObjectId();
        if (!empty($sOxId)) {
            $this->_aViewData["sOxid"] = $sOxId;
            $oOrder = $this->getEditObject();
            $this->_aViewData["oOrder"] = $oOrder;
            if (preg_match("/novalnet/i", $oOrder->oxorder__oxpaymenttype->value)) {
                $this->_aViewData['dIsNovalnetPayment'] = true;
                $this->_aViewData['iOrderNo'] = $oOrder->oxorder__oxordernr->value;
                $aTransDetails = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $oOrder->oxorder__oxordernr->value);
                // Update old txn details to New format
                if (!empty($aTransDetails) && $oOrder->oxorder__oxpaymenttype->value != 'novalnetpayments' && !preg_match("/novalnet_/i", $oOrder->oxorder__oxpaymenttype->value)) {
                    $aAdditionalData = unserialize($aTransDetails['ADDITIONAL_DATA']);
                    if (empty($aAdditionalData)) {
                        $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
                    }
                    if (!isset($aAdditionalData['updated_old_txn_details']) && $aAdditionalData['updated_old_txn_details'] != true) {
                        NovalnetUtil::convertOldTxnDetailsToNewFormat($oOrder->oxorder__oxordernr->value, $aTransDetails);
                        $aTransDetails = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $oOrder->oxorder__oxordernr->value);
                    }
                }

                //Get novalnet transaction comments
                $this->_aViewData['sNovalnetComments'] = NovalnetUtil::getNovalnetTransactionComments($oOrder->oxorder__oxordernr->value, true);
                $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
                $aInstalmentComment = $this->displayNovalnetActions($aAdditionalData);
                $this->_aViewData['aNovalnetInstalmentDetails'] = '';
                $this->_aViewData['blOnHold'] = false;
                $this->_aViewData['blAmountRefund'] = false;
                $this->_aViewData['blZeroBook'] = false;

                $bInstalmentRefund = false;
                if (!empty($aInstalmentComment)) {
                    $bInstalmentRefund = true;
                    $this->_aViewData['bShowCancel'] = 1;
                    $this->_aViewData['bHideRemainingCancel'] = 0;
                    $this->_aViewData['bHideAllCycleCancel'] = 0;
                    $this->_aViewData['aNovalnetInstalmentDetails']  = $aInstalmentComment;
                    if ($aTransDetails['AMOUNT'] == 0) {
                        $this->_aViewData['bShowCancel']  = 0;
                    }
                    if (!empty($aAdditionalData['cancel_remaining_cancel'])) {
                        $this->_aViewData['bHideRemainingCancel']  = 1;
                    }
                    if (!empty($aAdditionalData['cancel_all_cancel'])) {
                        $this->_aViewData['bHideAllCycleCancel']  = 1;
                    }
                }
                $this->_aViewData['dNovalnetAmount'] = $aTransDetails['AMOUNT'];
                $this->_aViewData['dCreditedAmount'] = $aTransDetails['CREDITED_AMOUNT'];
                $this->_aViewData['dOrderAmount'] = $oOrder->oxorder__oxtotalordersum->value * 100;
                if ($oOrder->oxorder__oxtotalordersum->value != '0') {
                    if ($aTransDetails['AMOUNT'] != 0) {
                        if ($aTransDetails['GATEWAY_STATUS'] == 'ON_HOLD') {
                            $this->_aViewData['blOnHold'] = true;
                        } elseif ($bInstalmentRefund == false && $aTransDetails['PAYMENT_TYPE'] != 'Multibanco') {
                            if ($aTransDetails['GATEWAY_STATUS'] == 'CONFIRMED' && $aTransDetails['CREDITED_AMOUNT'] > 0) {
                                $this->_aViewData['blAmountRefund'] = true;
                            }
                        }
                    }

                    if (!empty($aAdditionalData['zero_amount_booking']) && $aAdditionalData['zero_amount_booking'] == '1' && $aTransDetails['AMOUNT'] == '0' && $aTransDetails['GATEWAY_STATUS'] != 'DEACTIVATED') {
                        $this->_aViewData['blZeroBook'] = true;
                    }
                }
            }
            return "novalnet_order.tpl";
        }
    }

    /**
     * Returns editable order object
     *
     * @return object
     */
    public function getEditObject()
    {
        $soxId = $this->getEditObjectId();
        if (empty($this->_oEditObject) && isset($soxId) && $soxId != '-1') {
            $this->_oEditObject = oxNew(Order::class);
            $this->_oEditObject->load($soxId);
        }
        return $this->_oEditObject;
    }

    /**
     * Handles the Novalnet extension features visibility features
     *
     * @param  $aAdditionalData
     * @return array $aData
     */
    protected function displayNovalnetActions($aAdditionalData)
    {
        $aData = [];
        if (!empty($aAdditionalData['instalment_comments'])) {
            $aData = $aAdditionalData['instalment_comments'];
            $aData['cycles'] = [];
            for ($dCycle = 1; $dCycle <= $aData['instalment_total_cycles']; $dCycle++) {
                array_push($aData['cycles'], $dCycle);
            }
        }
        return $aData;
    }

    /**
     * Performs the Novalnet extension actions
     *
     * @return null
     */
    public function performNovalnetAction()
    {
        $aData = Registry::getConfig()->getRequestParameter('novalnet');
        if (!empty($aData) && isset($aData['iOrderNo']) && !empty($aData['iOrderNo']) && in_array($aData['sRequestType'], ['sOnHold', 'sZeroBook', 'sAmountRefund'])) {
            $aTransDetails = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $aData['iOrderNo']);
            $bProcessExecute = true;
            if ($aData['sRequestType'] == 'sAmountRefund' && $aTransDetails['CREDITED_AMOUNT'] <= 0) {
                $bProcessExecute = false;
                $this->_aViewData[$aData['sRequestType'] . 'Failure'] = 'Order already refunded with full amount';
            } elseif ($aData['sRequestType'] == 'sOnHold' && $aTransDetails['GATEWAY_STATUS'] != 'ON_HOLD') {
                $bProcessExecute = false;
                $this->_aViewData[$aData['sRequestType'] . 'Failure'] = 'Order already captured';
            } elseif ($aData['sRequestType'] == 'sZeroBook' && $aTransDetails['AMOUNT'] > 0) {
                $bProcessExecute = false;
                $this->_aViewData[$aData['sRequestType'] . 'Failure'] = 'Order already booked';
            }
            if ($bProcessExecute) {
                $bOldEntry = (!empty($aTransDetails['PAYMENT_ID'])) ? true : false;
                $aRequest['transaction']['tid'] = $aTransDetails['TID'];
                $aRequest['transaction']['order_no'] = $aData['iOrderNo'];
                if ($aData['sRequestType'] == 'sAmountRefund') {
                    $aRequest['transaction']['amount']   = $aData['sRefundAmount'];
                    $aRequest['transaction']['reason']   = $aData['sRefundReason'];
                }
                $aRequest['custom']['lang'] = strtoupper(Registry::getLang()->getLanguageAbbr(Registry::getLang()->getTplLanguage()));
                $aRequest['custom']['shop_invoked'] = 1;
                switch ($aData['sRequestType']) {
                    case 'sOnHold':
                        $sUrl = ($aData['sTransStatus'] == 100) ? 'transaction/capture' : 'transaction/cancel';
                        break;
                    case 'sZeroBook':
                        $sUrl = 'payment';
                        $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
                        $aZeroTxnRequest = $aAdditionalData['zero_request_data'];

                        if ($bOldEntry) {
                            $aZeroBookRequest = $this->formRequestParam($aZeroTxnRequest);
                            $aZeroBookRequest['transaction']['payment_data'] = ['payment_ref' => $aAdditionalData['zero_txn_reference']];
                        } else {
                            if (!empty($aZeroTxnRequest['event'])) {
                                $aZeroBookRequest['merchant'] = [
                                    'signature' => NovalnetUtil::getNovalnetConfigValue('sProductActivationKey'),
                                    'tariff'    => NovalnetUtil::getNovalnetConfigValue('sTariffId')
                                ];
                                $aZeroBookRequest['customer'] = $aZeroTxnRequest['customer'];
                                // Form order details parameters.
                                $aZeroBookRequest['transaction'] = [
                                    'payment_type'  => $aZeroTxnRequest['transaction']['payment_type'],
                                    'test_mode'     => $aZeroTxnRequest['transaction']['test_mode'],
                                    // Add Amount details.
                                    'currency'       => $aZeroTxnRequest['transaction']['currency'],
                                    // Add System details.
                                    'system_name'    => 'oxideshop',
                                    'system_url'     => Registry::getConfig()->getShopMainUrl(),
                                    'system_ip'      => NovalnetUtil::getIpAddress(true),
                                ];
                                $aZeroBookRequest['custom'] = $aZeroTxnRequest['custom'];
                            } else {
                                $aZeroBookRequest = $aZeroTxnRequest;
                            }
                            $aZeroBookRequest['transaction']['payment_data'] = ['token' => $aAdditionalData['zero_txn_reference']];
                        }
                        $aZeroBookRequest['transaction']['amount']      = $aData['sBookAmount'];
                        $aZeroBookRequest['transaction']['order_no']    = $aData['iOrderNo'];
                        if (!empty($aZeroBookRequest['transaction']['create_token'])) {
                            unset($aZeroBookRequest['transaction']['create_token']);
                        }
                        if (!empty($aZeroBookRequest['transaction']['return_url']) && !empty($aZeroBookRequest['transaction']['error_return_url'])) {
                            unset($aZeroBookRequest['transaction']['return_url']);
                            unset($aZeroBookRequest['transaction']['error_return_url']);
                        }
                        $aRequest = $aZeroBookRequest;
                        break;
                    case 'sAmountRefund':
                        $sUrl = 'transaction/refund';
                        $aData['amount'] = $aRequest['transaction']['amount'];
                        break;
                }
                $aResponse = NovalnetUtil::doCurlRequest($aRequest, $sUrl);
                if (!empty($aResponse['result']['status'])) {
                    if ($aResponse['result']['status'] == 'SUCCESS' && $aResponse['result']['status_code'] == '100') {
                        if (isset($aRequest['transaction']['tid']) && !empty($aRequest['transaction']['tid'])) {
                            $aData['tid']      = $aRequest['transaction']['tid'];
                        }
                        $this->updateNovalnetComments($aData, $aResponse);
                    } else {
                        $this->_aViewData[$aData['sRequestType'] . 'Failure'] = $aResponse['result']['status_text'];
                    }
                }
            }
        } elseif (!empty(Registry::getRequest()->getRequestParameter('serverResponse')) && ($aServerResponse = json_decode(Registry::getRequest()->getRequestParameter('serverResponse'), true)) && isset($aServerResponse['action']) && !empty($aServerResponse['action'])) {
            $aNovalnetComments = [];
            $aTransDetails = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $aServerResponse['order_id']);
            $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
            if ($aServerResponse['action'] == 'nn_refund') {
                $aData = [
                    'transaction' => [
                        'tid' => $aServerResponse['tid'],
                        'reason' => $aServerResponse['reason'],
                        'amount' => $aServerResponse['amount'],
                    ],
                    'custom' => [
                        'lang' =>  strtoupper(Registry::getLang()->getLanguageAbbr(Registry::getLang()->getTplLanguage())),
                        'shop_invoked' => 1,
                    ],
                ];
                $aResponse = NovalnetUtil::doCurlRequest($aData, 'transaction/refund');
                if (!empty($aResponse['result']['status'])) {
                    if ($aResponse['result']['status'] == 'SUCCESS') {
                        $iCreditedAmount = $aTransDetails['CREDITED_AMOUNT'] - $aServerResponse['amount'];
                        $aInstalmentDetails = $aAdditionalData['instalment_comments'];
                        for ($dCycle = 1; $dCycle <= $aInstalmentDetails['instalment_total_cycles']; $dCycle++) {
                            if (isset($aInstalmentDetails['instalment' . $dCycle]['tid']) && $aInstalmentDetails['instalment' . $dCycle]['tid'] == $aResponse['transaction']['tid']) {
                                $iAmount = $aResponse['transaction']['amount'] - $aResponse['transaction']['refunded_amount'];
                                if ($aInstalmentDetails['instalment' . $dCycle]['paid_amount'] == $aResponse['transaction']['refund']['amount']) {
                                    $aInstalmentDetails['instalment' . $dCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_REFUNDED';
                                }
                                $aInstalmentDetails['instalment' . $dCycle]['paid_amount'] = $iAmount;
                            }
                        }
                        $aAdditionalData['instalment_comments'] = $aInstalmentDetails;
                        $currency = !empty($aResponse['transaction']['refund']['currency']) ? $aResponse['transaction']['refund']['currency'] : $aResponse['transaction']['currency'];
                        if (isset($aResponse['transaction']['refund']['tid']) && !empty($aResponse['transaction']['refund']['tid'])) {
                            $aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TID_TEXT' => [$aResponse['transaction']['tid'], NovalnetUtil::formatCurrency($aResponse['transaction']['refund']['amount'], $currency) . ' ' . $currency, $aResponse['transaction']['refund']['tid']]];
                        } else {
                            $aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TEXT' => [$aResponse['transaction']['tid'], NovalnetUtil::formatCurrency($aResponse['transaction']['refund']['amount'], $currency) . ' ' . $currency]];
                        }
                        $aAdditionalData['novalnet_comments'][] = $aNovalnetComments;

                        NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['CREDITED_AMOUNT' => $iCreditedAmount,'ADDITIONAL_DATA' => json_encode($aAdditionalData)], 'ORDER_NO', $aServerResponse['order_id']);
                        $result = ['success' => true];
                    } else {
                        $result = ['success' => false, 'text' => $aResponse['result']['status_text']];
                    }
                    echo json_encode($result);
                    exit;
                }
            } elseif ($aServerResponse['action'] == 'cancel_all_instalment' || $aServerResponse['action'] == 'cancel_remaining_instalment') {
                $sCancelType = ($aServerResponse['action'] == 'cancel_all_instalment') ? 'CANCEL_ALL_CYCLES' : 'CANCEL_REMAINING_CYCLES';
                $aData = [
                    'instalment' => [
                        'tid' => $aServerResponse['parent_tid'],
                        'cancel_type' => $sCancelType,
                    ],
                    'custom' => [
                        'lang' => strtoupper(Registry::getLang()->getLanguageAbbr(Registry::getLang()->getTplLanguage())),
                        'shop_invoked' => 1,
                    ],
                ];
                $aResponse = NovalnetUtil::doCurlRequest($aData, 'instalment/cancel');
                if (!empty($aResponse['result']['status'])) {
                    if (!empty($aResponse['result']['status']) && $aResponse['result']['status'] == 'SUCCESS') {
                        $aInstalmentDetails = $aAdditionalData['instalment_comments'];
                        if ($sCancelType == 'CANCEL_ALL_CYCLES') {
                            $aAdditionalData['cancel_all_cancel'] = true;
                            for ($dCycle = 1; $dCycle <= $aInstalmentDetails['instalment_total_cycles']; $dCycle++) {
                                if (!isset($aInstalmentDetails['instalment' . $dCycle]['tid'])) {
                                    $aInstalmentDetails['instalment' . $dCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_CANCELLED';
                                } else {
                                    $aInstalmentDetails['instalment' . $dCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_REFUNDED';
                                }
                            }
                            $oxpaid = '0000-00-00 00:00:00';
                            $aNovalnetComments[] = ['NOVALNET_CALLBACK_INSTALMENT_CANCEL_MESSAGE' => [$aResponse['transaction']['tid'], NovalnetUtil::getFormatDate()]];
                            NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $oxpaid], 'OXORDERNR', $aServerResponse['order_id']);
                            $aAdditionalData['instalment_comments'] = $aInstalmentDetails;
                            $aAdditionalData['novalnet_comments'][] = $aNovalnetComments;
                            NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['ADDITIONAL_DATA' => json_encode($aAdditionalData),'GATEWAY_STATUS' => $aResponse['transaction']['status'], 'AMOUNT' => 0], 'ORDER_NO', $aServerResponse['order_id']);
                        } else {
                            $aAdditionalData['cancel_remaining_cancel'] = true;
                            $aNovalnetComments[] = ['NOVALNET_INSTALMENT_REMAINING_CANCEL_MESSAGE' => [$aTransDetails['TID'], NovalnetUtil::getFormatDate()]];
                            for ($dCycle = 1; $dCycle <= $aInstalmentDetails['instalment_total_cycles']; $dCycle++) {
                                if (!isset($aInstalmentDetails['instalment' . $dCycle]['tid'])) {
                                    $aInstalmentDetails['instalment' . $dCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_CANCELLED';
                                }
                            }
                            $aAdditionalData['instalment_comments'] = $aInstalmentDetails;
                            $aAdditionalData['novalnet_comments'][] = $aNovalnetComments;
                            NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['ADDITIONAL_DATA' => json_encode($aAdditionalData)], 'ORDER_NO', $aServerResponse['order_id']);
                        }
                        $result = ['success' => true];
                    } else {
                        $result = ['success' => false, 'text' => $aResponse['result']['status_text']];
                    }
                    echo json_encode($result);
                    exit;
                }
            }
        }
    }

    /**
     * Updates Novalnet comments in orders
     *
     * @param array $aData
     * @param array $aResponse
     *
     * @return null
     */
    protected function updateNovalnetComments($aData, $aResponse)
    {
        $iOrderNo = $aData['iOrderNo'];
        $sOxId  = $this->getEditObjectId();
        $aTransDetails = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $aData['iOrderNo']);
        $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
        $aNovalnetComments = $aInvoiceDetails = $aBankDetails = [];
        $oOrder = oxNew(Order::class);
        $oOrder->load($sOxId);
        $aUpdateData = [];
        if ($aData['sRequestType'] == 'sOnHold') {
            $sLang = ($aData['sTransStatus'] == 100) ? 'NOVALNET_STATUS_UPDATE_CONFIRMED_MESSAGE' : 'NOVALNET_STATUS_UPDATE_CANCELED_MESSAGE';

            if ($aData['sTransStatus'] == 100) {
                if (!empty($aResponse ['instalment']['cycle_amount'])) {
                    $sFormattedAmount = NovalnetUtil::formatCurrency($aResponse['instalment']['cycle_amount'], $oOrder->oxorder__oxcurrency->rawValue) . ' ' . $oOrder->oxorder__oxcurrency->rawValue;
                } else {
                    $sFormattedAmount = NovalnetUtil::formatCurrency($aResponse['transaction']['amount'], $oOrder->oxorder__oxcurrency->rawValue) . ' ' . $oOrder->oxorder__oxcurrency->rawValue;
                }

                if (in_array($aResponse['transaction']['payment_type'], ['INVOICE', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE'])) {
                    $aAdditionalData = json_decode($aTransDetails['ADDITIONAL_DATA'], true);
                    $aAdditionalData['novalnet_comments'] = [];
                    $aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$aResponse['transaction']['tid']]];
                    if (!empty($aResponse['transaction']['test_mode'])) {
                        $aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
                    }

                    if (!empty($aResponse['transaction']['bank_details'])) {
                        $aInvoiceDetails = NovalnetUtil::getInvoiceComments($aResponse);
                        $aAdditionalData['bank_details'] = $aInvoiceDetails;
                        $aNovalnetComments = array_merge($aNovalnetComments, $aInvoiceDetails);
                    } elseif (!empty($aResponse['transaction']['due_date'])) {
                        foreach ($aAdditionalData['bank_details'] as $aDetails => $aBankData) {
                            foreach ($aBankData as $sLangText => $sLangValue) {
                                if ($sLangText == 'NOVALNET_INSTALMENT_INVOICE_BANK_DESC') {
                                    $aNovalnetComments[] = ['NOVALNET_INSTALMENT_INVOICE_BANK_DESC_WITH_DUE' => [$sFormattedAmount,date('d.m.Y', strtotime($aResponse['transaction']['due_date']))]];
                                } elseif ($sLangText == 'NOVALNET_INVOICE_BANK_DESC') {
                                  $aNovalnetComments[] = ['NOVALNET_INVOICE_BANK_DESC_WITH_DUE' => [$sFormattedAmount,  date('d.m.Y', strtotime($aResponse['transaction']['due_date']))]];
                                } else {
                                    $aNovalnetComments[] = [$sLangText => $sLangValue];
                                }
                            }
                        }
                    }
                }
                $aNovalnetComments[] = [$sLang => [NovalnetUtil::getFormatDate(), date('H:i:s')]];

                if (isset($aResponse['transaction']['payment_type']) && in_array($aResponse['transaction']['payment_type'], array('INSTALMENT_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE'))) {
                    $aAdditionalData['instalment_comments'] = NovalnetUtil::formInstalmentData($aResponse, $aTransDetails['AMOUNT']);
                }
                if ($aResponse['transaction']['payment_type'] == 'INVOICE') {
                    $iCreditedAmount = 0;
                } else {
                    $iCreditedAmount = $aResponse['transaction']['amount'];
                }
                $aUpdateOrders = ['OXTRANSSTATUS' => 'NOT_FINISHED'];

                $aUpdateData = ['CREDITED_AMOUNT' => $iCreditedAmount, 'GATEWAY_STATUS' => $aResponse['transaction']['status']];
                if ($aResponse['transaction']['status'] == 'CONFIRMED' && isset($aResponse['transaction']['payment_type']) && $aResponse['transaction']['payment_type'] != 'INVOICE') {
                    $aUpdateOrders['OXPAID'] = NovalnetUtil::getFormatDateTime();
                    $aUpdateOrders['OXTRANSSTATUS'] = 'OK';
                } elseif ($aResponse['transaction']['payment_type'] == 'INVOICE') {
                    $aUpdateOrders['OXTRANSSTATUS'] = 'OK';
                }
                NovalnetUtil::updateTableValues('oxorder', $aUpdateOrders, 'OXORDERNR', $iOrderNo);
            } else {
                $aUpdateData = ['GATEWAY_STATUS' => $aResponse['transaction']['status']];
                $aNovalnetComments[] = [$sLang => [NovalnetUtil::getFormatDate(), date('H:i:s')]];
            }
        } elseif ($aData['sRequestType'] == 'sAmountRefund') {
            $currency = !empty($aResponse['transaction']['refund']['currency']) ? $aResponse['transaction']['refund']['currency'] : $aResponse['transaction']['currency'];
            $sFormattedAmount = NovalnetUtil::formatCurrency($aResponse['transaction']['refund']['amount'], $oOrder->oxorder__oxcurrency->rawValue) . ' ' . $currency;
            if (isset($aResponse['transaction']['refund']['tid']) && !empty($aResponse['transaction']['refund']['tid'])) {
                $aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TID_TEXT' => [$aResponse['transaction']['tid'], $sFormattedAmount, $aResponse['transaction']['refund']['tid']]];
            } else {
                $aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TEXT' => [$aResponse['transaction']['tid'], $sFormattedAmount]];
            }
            $iCreditedAmount = $aTransDetails['CREDITED_AMOUNT'] - $aResponse['transaction']['refund']['amount'];
            $aUpdateData = ['CREDITED_AMOUNT' => $iCreditedAmount];
        } elseif ($aData['sRequestType'] == 'sZeroBook') {
            $sFormattedAmount = NovalnetUtil::formatCurrency($aData['sBookAmount'], $oOrder->oxorder__oxcurrency->rawValue) . ' ' . $oOrder->oxorder__oxcurrency->rawValue;
            $aNovalnetComments[] = ['NOVALNET_AMOUNT_BOOKED_MESSAGE' => [$sFormattedAmount, $aResponse['transaction']['tid']]];
            $aUpdateData = ['AMOUNT' => $aData['sBookAmount'], 'CREDITED_AMOUNT' => $aData['sBookAmount'], 'TID' => $aResponse['transaction']['tid']];
            $aUpdateOrders = ['OXTRANSSTATUS' => 'OK', 'OXPAID' => NovalnetUtil::getFormatDateTime()];
            NovalnetUtil::updateTableValues('oxorder', $aUpdateOrders, 'OXORDERNR', $iOrderNo);
        }
        if (!empty($aNovalnetComments)) {
            $aComments = array_merge($aNovalnetComments, $aInvoiceDetails, $aBankDetails);
            $aAdditionalData['novalnet_comments'][] = $aComments;
        }
        $aUpdateData['ADDITIONAL_DATA'] = json_encode($aAdditionalData);
        NovalnetUtil::updateTableValues('novalnet_transaction_detail', $aUpdateData, 'ORDER_NO', $iOrderNo);
    }

    /**
     * Form request parameters for booking the amount
     *
     * @param array $aRequest
     *
     * @return array $aNovalnetRequest
     */
    public function formRequestParam($aRequest)
    {
        $aNovalnetRequest = [];
        $aNovalnetRequest['merchant'] = [
            'signature' => NovalnetUtil::getNovalnetConfigValue('sProductActivationKey'),
            'tariff'    => NovalnetUtil::getNovalnetConfigValue('sTariffId')
        ];
        $aNovalnetRequest['transaction'] = [
            'payment_type'   => $aRequest['payment_type'],
            'test_mode'      => $aRequest['test_mode'],
            'currency'       => $aRequest['currency'],
            'system_name'    => $aRequest['system_name'],
            'system_version' => ShopVersion::getVersion() . '-NN' . self::$sNovalnetVersion,
            'system_url'     => Registry::getConfig()->getShopMainUrl(),
            'system_ip'      => NovalnetUtil::getIpAddress(true),
        ];
        $aNovalnetRequest['customer'] = [
            'first_name'  => $aRequest['first_name'],
            'last_name'   => $aRequest['last_name'],
            'email'       => $aRequest['email'],
            'tel'         => $aRequest['tel'],
            'customer_ip' => NovalnetUtil::getIpAddress(),
            'customer_no' => $aRequest['customer_no'],
        ];
        $aNovalnetRequest['customer']['billing'] = [
            'street'       => $aRequest['street'],
            'city'         => $aRequest['city'],
            'zip'          => $aRequest['zip'],
            'country_code' => $aRequest['country_code'],
            'house_no'     => $aRequest['house_no'],
        ];
        $aNovalnetRequest['customer']['shipping'] = [
            'same_as_billing' => 1,
        ];
        $aNovalnetRequest['custom'] = [
            'lang' => $aRequest['lang'],
        ];
        return $aNovalnetRequest;
    }
}
