<?php

/**
 * Novalnet payment module
 *
 * This file is used for asynchronuous process
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: CallbackController.php
 */

namespace oe\novalnet\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use oe\novalnet\Core\NovalnetUtil;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Counter;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\UserBasket;

/**
 * Class CallbackController.
 */
class CallbackController extends FrontendController
{
    protected $_sThisTemplate    = 'novalnet_callback.tpl';

    /**
     * Allowed host from Novalnet.
     *
     * @var string
     */
    protected $sNovalnetHostName = 'pay-nn.de';

    protected $_aViewData; // View data array

    /**
     * Mandatory Parameters.
     *
     * @var array
     */
    protected $mandatory = [
        'event' => [
            'type',
            'checksum',
            'tid'
        ],
        'result' => [
            'status'
        ],
    ];

    /**
     * Callback test mode.
     *
     * @var int
     */
    protected $blCallbackTestMode;

    /**
     * Request parameters.
     *
     * @var array
     */
    protected $eventData = array();

    /**
     * Your payment access key value
     *
     * @var string
     */
    protected $sPaymentAccesskey;

    /**
     * Order reference values.
     *
     * @var array
     */
    protected $aorderReference = array();

    /**
     * Recived Event type.
     *
     * @var string
     */
    protected $eventType;

    /**
     * Recived Event TID.
     *
     * @var int
     */
    protected $eventTID;

    /**
     * Recived Event parent TID.
     *
     * @var int
     */
    protected $parentTID;

    /**
     * Order Id.
     *
     * @var int
     */
    protected $orderID;

    /**
     * Received amount.
     *
     * @var int
     */
    protected $receivedAmount;

    /**
     * Get Additional Data.
     *
     * @var array
     */
    protected $aAdditionalData;

    /**
     * Form Novalnet Comments.
     *
     * @var array
     */
    protected $aNovalnetComments;

    /**
     * Get database object.
     *
     * @var object
     */
    protected $oDb;

    /**
     * Returns name of template to render
     *
     * @return string
     */
    public function render()
    {
        return $this->_sThisTemplate;
    }

    /**
     * Novalnet_Webhooks constructor.
     */
    public function handleRequest()
    {
        $this->_aViewData['sNovalnetMessage'] = '';
        try {
            $this->eventData = json_decode(file_get_contents('php://input'), true);
        } catch (\Exception $e) {
            $this->displayMessage(['message' => "Received data is not in the JSON format $e"]);
            return false;
        }
        if (empty($this->eventData)) {
            $this->displayMessage(['message' => "Received data is not in the JSON format"]);
            return false;
        }
        // Backend callback option.
        $this->blCallbackTestMode  = NovalnetUtil::getNovalnetConfigValue('blWebhookNotification'); // Webhook run with test mode or not
        $this->sPaymentAccesskey = NovalnetUtil::getNovalnetConfigValue('sPaymentAccessKey');// Payment access key value
        $this->oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        // Authenticating the server request based on IP.
        // Host based validation
        if (!empty($this->sNovalnetHostName)) {
            $mNovalnetHostIp  = gethostbyname($this->sNovalnetHostName);
            $requestReceivedIp = NovalnetUtil::checkWebhookIp($mNovalnetHostIp);
            if (empty($requestReceivedIp) && empty($this->blCallbackTestMode)) {
                $this->displayMessage(['message' => 'Unauthorised access from the IP ' . NovalnetUtil::getIpAddress()]);
                return false;
            }
        } else {
            $this->displayMessage(['message' => 'Unauthorised access from the IP. Novalnet Host name is empty']);
            return false;
        }
        // Validate mandatory parameters
        if (!$this->validateEventData()) {
            return false;
        }
        // Validate checksum
        if (!$this->validateChecksum()) {
            return false;
        }
        if (!empty($this->eventData['custom']['shop_invoked'])) {
            $this->displayMessage([ 'message' => 'Process already handled in the shop.']);
            return false;
        }
        // Set Event data
        $this->eventType = $this->eventData['event']['type'];
        $this->parentTID = !empty($this->eventData['event']['parent_tid']) ? $this->eventData['event']['parent_tid'] : $this->eventData ['event']['tid'];
        $this->eventTID  = $this->eventData['event']['tid'];
        // Get order reference.
        $this->aorderReference = $this->getOrderReference();
        if (empty($this->aorderReference)) {
            return false;
        }
        $this->aAdditionalData = json_decode($this->aorderReference['ADDITIONAL_DATA'], true);
        $this->aNovalnetComments = [];
        $this->orderID = (isset($this->eventData['transaction']['order_no']) && !empty($this->eventData['transaction']['order_no'])) ? $this->eventData['transaction']['order_no'] : $this->aorderReference['ORDER_NO'];
        // Format amount as per shop structure
        if (!empty($this->eventData ['instalment']['cycle_amount'])) {
            $this->receivedAmount = NovalnetUtil::formatCurrency($this->eventData['instalment']['cycle_amount'], $this->eventData['transaction']['currency']) . ' ' . $this->eventData['transaction']['currency'];
        } elseif (!empty($this->eventData['transaction']['amount'])) {
            $this->receivedAmount = NovalnetUtil::formatCurrency($this->eventData['transaction']['amount'], $this->eventData['transaction']['currency']) . ' ' . $this->eventData['transaction']['currency'];
        }

        switch ($this->eventType) {
            case "PAYMENT":
                // Handle initial PAYMENT notification (incl. communication failure, Authorization).
                $this->displayMessage(['message' => "Novalnet Callback executed. The Transaction ID already existed"]);
                break;
            case "TRANSACTION_CAPTURE":
            case "TRANSACTION_CANCEL":
                $this->handleTransactionCaptureCancel();
                break;
            case "TRANSACTION_REFUND":
                $this->handleTransactionRefund();
                break;
            case "TRANSACTION_UPDATE":
                $this->handTransactionUpdate();
                break;
            case "CREDIT":
                $this->handleCredit();
                break;
            case "CHARGEBACK":
                $this->handleChargeback();
                break;
            case "INSTALMENT":
                $this->handleInstalment();
                break;
            case "INSTALMENT_CANCEL":
                $this->handleInstalmentCancel();
                break;
            case "PAYMENT_REMINDER_1":
                $this->handlePaymentReminderAndCollection();
                break;
            case "PAYMENT_REMINDER_2":
                $this->handlePaymentReminderAndCollection();
                break;
            case "SUBMISSION_TO_COLLECTION_AGENCY":
                $this->handlePaymentRemainterAndCollection();
                break;
            default:
                $this->displayMessage(['message' => "The webhook notification has been received for the unhandled EVENt type($this->eventType)"]);
        }
    }

    /**
     * Validate server request
     *
     * @return boolean
     */
    protected function validateEventData()
    {
        // Validate required parameter
        foreach ($this->mandatory as $category => $parameters) {
            if (empty($this->eventData[$category])) {
                // Could be a possible manipulation in the notification data
                $this->displayMessage(['message' => "Required parameter category($category) not received" ]);
                return false;
            } elseif (!empty($parameters)) {
                foreach ($parameters as $parameter) {
                    if (empty($this->eventData[$category][$parameter])) {
                        // Could be a possible manipulation in the notification data
                        $this->displayMessage(['message' => "Required parameter($parameter) in the category($category) not received"]);
                        return false;
                    } elseif (in_array($parameter, ['tid'], true) && !NovalnetUtil::validTid($this->eventData[$category][$parameter])) {
                        $this->displayMessage(['message' => "Invalid TID received in the category($category) not received $parameter"]);
                        return false;
                    }
                }
            }
        }
        // Validate TID's from the event data
        if (!NovalnetUtil::validTid($this->eventData['event']['tid'])) {
            $this->displayMessage(['message' => "Invalid event TID: " . $this->eventData ['event']['tid'] . " received for the event(" . $this->eventData ['event']['type'] . ")"]);
            return false;
        } elseif (!empty($this->eventData['event']['parentTID']) && !NovalnetUtil::validTid($this->eventData['event']['parentTID'])) {
            $this->displayMessage(['message' => "Invalid event TID: " . $this->eventData['event']['parentTID'] . " received for the event(" . $this->eventData ['event']['type'] . ")"]);
            return false;
        }
        return true;
    }

    /**
     * Handle transaction capture/cancel
     *
     * @return null
     */
    public function handleTransactionCaptureCancel()
    {
        if (in_array($this->aorderReference['GATEWAY_STATUS'], ['ON_HOLD', 'PENDING'])) {
            $sMessage = ($this->eventType == 'TRANSACTION_CAPTURE') ? 'NOVALNET_STATUS_UPDATE_CONFIRMED_MESSAGE' : 'NOVALNET_STATUS_UPDATE_CANCELED_MESSAGE';
            $oxpaid = '0000-00-00 00:00:00';

            $aUpdateData = [];
            $status = 'NOT_FINISHED';

            if ($this->eventType == 'TRANSACTION_CAPTURE') {
                $oxpaid = ($this->eventData['transaction']['payment_type'] == 'INVOICE') ? '0000-00-00 00:00:00' : NovalnetUtil::getFormatDateTime();
                if (in_array($this->eventData['transaction']['payment_type'], ['INVOICE', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE'])) {
                    $this->aAdditionalData['novalnet_comments'] = [];
                    $this->aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->eventData['transaction']['tid']]];
                    if (!empty($this->eventData['transaction']['test_mode'])) {
                        $this->aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
                    }

                    // Format the invoice comments
                    $this->getFormattedInvoiceComments();
                }
                if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                    $this->aAdditionalData['instalment_comments'] = NovalnetUtil::formInstalmentData($this->eventData, $this->aorderReference['AMOUNT']);
                }
                if ($this->eventData['transaction']['payment_type'] == 'INVOICE') {
                    $iCreditedAmount = 0;
                } else {
                    $iCreditedAmount = $this->eventData['transaction']['amount'];
                }

                if (($this->eventData['transaction']['status'] == 'CONFIRMED') || ($this->eventData['transaction']['payment_type'] == 'INVOICE')) {
                    $status = 'OK';
                }

                $aUpdateData['CREDITED_AMOUNT'] = $iCreditedAmount;
            }
            $this->aNovalnetComments[] = [$sMessage => [NovalnetUtil::getFormatDate(), date('H:i:s')]];
            $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
            NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $oxpaid, 'OXTRANSSTATUS' => $status], 'OXORDERNR', $this->orderID);
            $aUpdateData['ADDITIONAL_DATA'] = json_encode($this->aAdditionalData);
            $aUpdateData['GATEWAY_STATUS'] = $this->eventData['transaction']['status'];
            NovalnetUtil::updateTableValues('novalnet_transaction_detail', $aUpdateData, 'ORDER_NO', $this->orderID);
            $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        } else {
            $this->displayMessage(['message' => 'Order already captured']);
        }
    }

    /**
     * Handle transaction refund
     *
     * @return null
     */
    public function handleTransactionRefund()
    {
        if ($this->aorderReference['GATEWAY_STATUS'] == 'CONFIRMED' && !empty($this->eventData['transaction']['refund']['amount']) && $this->aorderReference['CREDITED_AMOUNT'] > 0) {
            $currency = !empty($this->eventData['transaction']['refund']['currency']) ? $this->eventData['transaction']['refund']['currency'] : $this->eventData['transaction']['currency'];
            $dRefundAmount = NovalnetUtil::formatCurrency($this->eventData['transaction']['refund']['amount'], $currency) . ' ' . $currency;
            if (isset($this->eventData['transaction']['refund']['tid']) && !empty($this->eventData['transaction']['refund']['tid'])) {
                $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TID_TEXT' => [$this->eventData['event']['parent_tid'], $dRefundAmount, $this->eventData['event']['tid']]];
            } else {
                $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_REFUND_TEXT' => [$this->eventData['transaction']['tid'], $dRefundAmount]];
            }
            if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                $this->aAdditionalData['instalment_comments'] = $this->updateInstalmentData();
            }
            $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
            $iCreditedAmount = $this->aorderReference['CREDITED_AMOUNT'] - $this->eventData['transaction']['refund']['amount'];
            NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['CREDITED_AMOUNT' => $iCreditedAmount, 'ADDITIONAL_DATA' => json_encode($this->aAdditionalData)], 'ORDER_NO', $this->orderID);
            $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        } else {
            $this->displayMessage(['message' => 'Order already refunded with full amount']);
        }
    }

    /**
     * Handle transaction update
     *
     * @return null
     */
    public function handTransactionUpdate()
    {
        $aUpdateData = [];
        $aInvoiceDetails = [];
        if ($this->eventData['transaction']['update_type'] == 'STATUS') {
            if (in_array($this->eventData['transaction']['status'], ['PENDING', 'ON_HOLD', 'CONFIRMED', 'DEACTIVATED'])) {
                if ($this->eventData['transaction']['status'] == 'DEACTIVATED') {
                    $oxpaid    = '0000-00-00 00:00:00';
                    $this->aNovalnetComments[] = ['NOVALNET_STATUS_UPDATE_CANCELED_MESSAGE' => [NovalnetUtil::getFormatDate(), date('H:i:s')]];
                    NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $oxpaid, 'OXTRANSSTATUS' => 'NOT_FINISHED'], 'OXORDERNR', $this->orderID);
                } else {
                    if ($this->eventData['transaction']['status'] == 'ON_HOLD') {
                        if (in_array($this->eventData['transaction']['payment_type'], ['GUARANTEED_INVOICE', 'INSTALMENT_INVOICE', 'INVOICE'])) {
                            $this->aAdditionalData['novalnet_comments'] = [];
                            $this->aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->eventData['transaction']['tid']]];
                            if (!empty($this->eventData['transaction']['test_mode'])) {
                                $this->aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
                            }
                            // Format the invoice comments
                            $this->getFormattedInvoiceComments();
                        }
                        $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_UPDATE_STATUS_ONHOLD' => [$this->eventData['transaction']['tid'], NovalnetUtil::getFormatDateTime()]];
                    } elseif ($this->eventData['transaction']['status'] == 'CONFIRMED') {
                        if (in_array($this->eventData['transaction']['payment_type'], ['INVOICE', 'GUARANTEED_INVOICE', 'INSTALMENT_INVOICE'])) {
                            $this->aAdditionalData['novalnet_comments'] = [];
                            $this->aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->eventData['transaction']['tid']]];
                            if (!empty($this->eventData['transaction']['test_mode'])) {
                                $this->aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
                            }

                            // Format the invoice comments
                            $this->getFormattedInvoiceComments();
                        }

                        $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_UPDATE_STATUS_UPDATE' => [$this->eventData['transaction']['tid'], NovalnetUtil::getFormatDate()]];

                        if (in_array($this->eventData['transaction']['payment_type'], ['INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'])) {
                            $this->aAdditionalData['instalment_comments'] = NovalnetUtil::formInstalmentData($this->eventData, $this->aorderReference['AMOUNT']);
                        }
                        if ($this->eventData['transaction']['payment_type'] != 'INVOICE') {
                            $aUpdateData = ['CREDITED_AMOUNT' => $this->eventData['transaction']['amount']];
                            NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => NovalnetUtil::getFormatDateTime(), 'OXTRANSSTATUS' => 'OK'], 'OXORDERNR', $this->orderID);
                        }
                    }
                }
                $aUpdateData['GATEWAY_STATUS'] = $this->eventData['transaction']['status'];
            }
        } elseif (in_array($this->eventData['transaction']['update_type'], ['DUE_DATE', 'AMOUNT_DUE_DATE'])) {
            $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_UPDATE_DUEDATE' => [$this->eventData['transaction']['tid'], $this->receivedAmount, $this->eventData['transaction']['due_date']]];
            $aUpdateData = ['AMOUNT' => $this->eventData['transaction']['amount']];
        } elseif ($this->eventData['transaction']['update_type'] == 'AMOUNT') {
            $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_UPDATE_AMOUNT' => [$this->eventData['transaction']['tid'], $this->receivedAmount]];
            $aUpdateData = ['AMOUNT' => $this->eventData['transaction']['amount']];
        }
        $aComments = $this->aNovalnetComments;
        $this->aAdditionalData['novalnet_comments'][] = $aComments;
        $aUpdateData['ADDITIONAL_DATA'] = json_encode($this->aAdditionalData);
        NovalnetUtil::updateTableValues('novalnet_transaction_detail', $aUpdateData, 'ORDER_NO', $this->orderID);
        $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
        if (!empty($sComments)) {
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        }
    }

    /**
     * Handle transaction credit
     *
     * @return null
     */
    public function handleCredit()
    {
        $dTotalAmount = $this->aorderReference['CREDITED_AMOUNT'] + $this->eventData['transaction']['amount'];
        if ($dTotalAmount >= $this->aorderReference['AMOUNT']) {
            NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => NovalnetUtil::getFormatDateTime(), 'OXTRANSSTATUS' => 'OK'], 'OXORDERNR', $this->orderID);
        }
        $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_CREDIT' => [$this->eventData['event']['parent_tid'], $this->receivedAmount, NovalnetUtil::getFormatDate(), $this->eventData['transaction']['tid']]];
        $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
        $aUpdateNovalnetDetails = ['GATEWAY_STATUS' => $this->eventData['transaction']['status'], 'CREDITED_AMOUNT' => $dTotalAmount, 'ADDITIONAL_DATA' => json_encode($this->aAdditionalData)];
        if ($this->eventData['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
            if ($this->aorderReference['AMOUNT'] == 0) {
                $aUpdateNovalnetDetails['AMOUNT'] = $this->eventData['transaction']['amount'];
                NovalnetUtil::updateTableValues('oxorder', ['OXFOLDER' => 'ORDERFOLDER_NEW', 'OXTRANSSTATUS' => 'OK'], 'OXID', $this->eventData['custom']['inputval2']);
            }
        }
        NovalnetUtil::updateTableValues('novalnet_transaction_detail', $aUpdateNovalnetDetails, 'ORDER_NO', $this->orderID);
        $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
        if (!empty($sComments)) {
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        }
    }

    /**
     * Handle chargeback/ return debit
     *
     * @return null
     */
    public function handleChargeback()
    {
        if ($this->aorderReference['GATEWAY_STATUS'] == 'CONFIRMED' && $this->aorderReference['AMOUNT'] != 0 && !empty($this->eventData['transaction']['amount'])) {
            $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_CHARGEBACK' => [$this->eventData['event']['parent_tid'], $this->receivedAmount, NovalnetUtil::getFormatDate(), $this->eventData['event']['tid']]];
            $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
            $iCreditedAmount = $this->aorderReference['CREDITED_AMOUNT'] - $this->eventData['transaction']['amount'];
            NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['CREDITED_AMOUNT' => $iCreditedAmount, 'ADDITIONAL_DATA' => json_encode($this->aAdditionalData)], 'ORDER_NO', $this->orderID);
            $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        }
    }

    /**
     * Handle instalment
     *
     * @return null
     */
    public function handleInstalment()
    {
        if ('CONFIRMED' == $this->aorderReference['GATEWAY_STATUS'] && $this->eventData['instalment']['cycles_executed'] != '0') {
            // Check the total instalment cycle
            $total_cycle = $this->aAdditionalData['instalment_comments'];
            if ($this->eventData['instalment']['cycles_executed'] <= $total_cycle['instalment_total_cycles']) {
                $dInstalmentAmount = NovalnetUtil::formatCurrency($this->eventData['instalment']['cycle_amount'], $this->eventData['transaction']['currency']) . ' ' . $this->eventData['transaction']['currency'];
                $this->aAdditionalData['novalnet_comments'] = [];
                $this->aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->eventData['transaction']['tid']]];
                if (!empty($this->eventData['transaction']['test_mode'])) {
                    $this->aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
                }

                if (empty($this->eventData['instalment']['prepaid'])) {
                    // Format the invoice comments
                    $this->getFormattedInvoiceComments();
                }

                $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_INSTALMENT_MESSAGE' => [$this->eventData['event']['parent_tid'], $this->eventData['transaction']['tid'], $dInstalmentAmount, date('Y-m-d H:i:s')]];
                $this->aAdditionalData['instalment_comments'] = $this->storeInstalmentData();
                $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
                $iCreditedAmount = $this->aorderReference['CREDITED_AMOUNT'] + $this->eventData['instalment']['cycle_amount'];
                NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['CREDITED_AMOUNT' => $iCreditedAmount, 'ADDITIONAL_DATA' => json_encode($this->aAdditionalData)], 'ORDER_NO', $this->orderID);
                $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
                $this->sendNotifyMail($sComments);
                $this->displayMessage(['message' => $sComments]);
            } else {
                $this->displayMessage(['message' => 'Instalment cycle already completed..!']);
            }
        }
    }

    /**
     * Format the invoice comments
     */
    public function getFormattedInvoiceComments()
    {
        if (!empty($this->eventData['transaction']['bank_details'])) {
            $aInvoiceDetails = NovalnetUtil::getInvoiceComments($this->eventData);
            $this->aAdditionalData['bank_details'] = $aInvoiceDetails;
            $this->aNovalnetComments = array_merge($this->aNovalnetComments, $aInvoiceDetails);
        } elseif (!empty($this->aAdditionalData['bank_details']) && !empty($this->eventData['transaction']['due_date'])) {
            foreach ($this->aAdditionalData['bank_details'] as $key => $array) {
                foreach ($array as $sLangText => $sLangValue) {
                    if ($sLangText == 'NOVALNET_INSTALMENT_INVOICE_BANK_DESC') {
                        $this->aNovalnetComments[] = ['NOVALNET_INSTALMENT_INVOICE_BANK_DESC_WITH_DUE' => [$this->receivedAmount,  date('d.m.Y', strtotime($this->eventData['transaction']['due_date']))]];
                    } elseif ($sLangText == 'NOVALNET_INVOICE_BANK_DESC') {
                        $this->aNovalnetComments[] = ['NOVALNET_INVOICE_BANK_DESC_WITH_DUE' => [$this->receivedAmount,  date('d.m.Y', strtotime($this->eventData['transaction']['due_date']))]]; 
                    } else {

                        if (isset($this->eventData['event']['parent_tid']) && $this->eventData['event']['parent_tid'] == $sLangValue[0]) {
                            $this->aNovalnetComments[] = [ $sLangText => [$this->eventData['transaction']['tid']]];
                        } else {
                            $this->aNovalnetComments[] = [ $sLangText => $sLangValue];
                        }
                    }
                }
            }
        }
    }

    /**
     * Handle instalment cancel
     *
     * @return null
     */
    public function handleInstalmentCancel()
    {
        if ($this->eventData['transaction']['status'] == 'CONFIRMED') {
            $aInstalmentDetails = $this->aAdditionalData['instalment_comments'];
            if ($this->eventData['instalment']['cancel_type'] == 'ALL_CYCLES') {
                $this->aAdditionalData['cancel_all_cancel'] = true;
                $oxpaid = '0000-00-00 00:00:00';
                for ($dcycle = 1; $dcycle <= $aInstalmentDetails['instalment_total_cycles']; $dcycle++) {
                    if (!isset($aInstalmentDetails['instalment' . $dcycle]['tid'])) {
                        $aInstalmentDetails['instalment' . $dcycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_CANCELLED';
                    } else {
                        $aInstalmentDetails['instalment' . $dcycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_REFUNDED';
                    }
                }
                $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_INSTALMENT_CANCEL_MESSAGE' => [$this->eventData['transaction']['tid'], NovalnetUtil::getFormatDate()]];
                $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
                $this->aAdditionalData['instalment_comments'] = $aInstalmentDetails;
                NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $oxpaid], 'OXORDERNR', $this->orderID);
                NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['ADDITIONAL_DATA' => json_encode($this->aAdditionalData), 'GATEWAY_STATUS' => $this->eventData['transaction']['status']], 'ORDER_NO', $this->orderID);
            } elseif ($this->eventData['instalment']['cancel_type'] == 'REMAINING_CYCLES') {
                $this->aAdditionalData['cancel_remaining_cancel'] = true;
                $this->aNovalnetComments[] = ['NOVALNET_INSTALMENT_REMAINING_CANCEL_MESSAGE' => [$this->aorderReference['TID'], NovalnetUtil::getFormatDate()]];
                for ($dCycle = 1; $dCycle <= $aInstalmentDetails['instalment_total_cycles']; $dCycle++) {
                    if (!isset($aInstalmentDetails['instalment' . $dCycle]['tid'])) {
                        $aInstalmentDetails['instalment' . $dCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_CANCELLED';
                    }
                }
                $this->aAdditionalData['instalment_comments'] = $aInstalmentDetails;
                $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
                NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['ADDITIONAL_DATA' => json_encode($this->aAdditionalData)], 'ORDER_NO', $this->orderID);
            }
            $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
            $this->sendNotifyMail($sComments);
            $this->displayMessage(['message' => $sComments]);
        }
    }
    /**
     * Handle payment reminder and collections
     *
     * @return null
     */
    public function handlePaymentReminderAndCollection()
    {
        if ($this->eventType == 'SUBMISSION_TO_COLLECTION_AGENCY') {
            $this->aNovalnetComments[] = ['NOVALNET_COLLECTION_AGENCY_MESSAGE' => [$this->eventData['collection']['reference']]];
        } else {
            $this->aNovalnetComments[] = ($this->eventType == 'PAYMENT_REMINDER_1') ? ['NOVALNET_PAYMENT_REMAINTER1_MESSAGE' => [null]] : ['NOVALNET_PAYMENT_REMAINTER2_MESSAGE' => [null]];
        }
        $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
        NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['ADDITIONAL_DATA' => json_encode($this->aAdditionalData)], 'ORDER_NO', $this->orderID);
        $sComments = NovalnetUtil::getTranslateComments($this->aNovalnetComments, $this->aorderReference['LANG']);
        $this->sendNotifyMail($sComments);
        $this->displayMessage(['message' => $sComments]);
    }

    /**
     * Form instalment comment
     *
     * @return array $aInstalmentDetails
     */
    public function storeInstalmentData()
    {
        $aInstalmentDetails = $this->aAdditionalData['instalment_comments'];
        if (!empty($aInstalmentDetails)) {
            $currency = Registry::getConfig()->getCurrencyObject($this->eventData['transaction']['currency']);
            $iCyclesExecuted = $this->eventData['instalment']['cycles_executed'];
            $aNextInstalment['tid'] = $this->eventData['transaction']['tid'];
            $aNextInstalment['amount'] = NovalnetUtil::formatCurrency($this->eventData['instalment']['cycle_amount'], $this->eventData['transaction']['currency']) . ' ' . $currency->sign;
            $aNextInstalment['paid_amount'] = $this->eventData['instalment']['cycle_amount'];
            $aNextInstalment['paid_date'] = date('Y-m-d', strtotime(NovalnetUtil::getFormatDateTime()));
            $aNextInstalment['status'] = 'NOVALNET_INSTALMENT_STATUS_COMPLETED';
            $aNextInstalment['next_instalment_date'] = (!empty($this->eventData['instalment']['next_cycle_date'])) ? date('Y-m-d', strtotime($this->eventData['instalment']['next_cycle_date'])) : '';
            $aNextInstalment['instalment_cycles_executed'] = $this->eventData['instalment']['cycles_executed'];
            $aNextInstalment['due_instalment_cycles'] = $this->eventData['instalment']['pending_cycles'];
            $aInstalmentDetails['instalment' . $iCyclesExecuted] = $aNextInstalment;
        }
        return $aInstalmentDetails;
    }

    /**
     * Update instalment comment
     *
     * @return array $aInstalmentDetails
     */
    public function updateInstalmentData()
    {
        $aInstalmentDetails = $this->aAdditionalData['instalment_comments'];
        for ($dCycleCount = 1; $dCycleCount <= $aInstalmentDetails['instalment_total_cycles']; $dCycleCount++) {
            if (isset($aInstalmentDetails['instalment' . $dCycleCount]['tid']) && $aInstalmentDetails['instalment' . $dCycleCount]['tid'] == $this->parentTID) {
                $iAmount = $this->eventData['transaction']['amount'] - $this->eventData['transaction']['refunded_amount'];
                if ($aInstalmentDetails['instalment' . $dCycleCount]['paid_amount'] == $this->eventData['transaction']['refund']['amount']) {
                    $aInstalmentDetails['instalment' . $dCycleCount]['status'] = 'NOVALNET_INSTALMENT_STATUS_REFUNDED';
                }
                $aInstalmentDetails['instalment' . $dCycleCount]['paid_amount'] = $iAmount;
            }
        }
        return $aInstalmentDetails;
    }

    /**
     * Validate checksum
     *
     * @return boolean
     */
    protected function validateChecksum()
    {
        $mxTokenString  = $this->eventData['event']['tid'] . $this->eventData['event']['type'] . $this->eventData['result']['status'];
        if (isset($this->eventData['transaction']['amount'])) {
            $mxTokenString .= $this->eventData['transaction']['amount'];
        }
        if (isset($this->eventData['transaction']['currency'])) {
            $mxTokenString .= $this->eventData['transaction']['currency'];
        }
        if (!empty($this->sPaymentAccesskey)) {
            $mxTokenString .= strrev($this->sPaymentAccesskey);
        }
        $mxGeneratedChecksum = hash('sha256', $mxTokenString);
        if ($mxGeneratedChecksum != $this->eventData['event']['checksum']) {
            $this->displayMessage(['message' => "While notifying some data has been changed. The hash check failed"]);
            return false;
        }
        return true;
    }

    /**
     * Get order reference.
     *
     * @return array
     */
    protected function getOrderReference()
    {
        $aDbValue = $aResult = [];

        $aDbValue = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'TID', $this->parentTID);
        $sOrderNo = !empty($aDbValue['ORDER_NO']) ? $aDbValue['ORDER_NO'] : $this->eventData['transaction']['order_no'];
        $aResult = NovalnetUtil::getTableValues('OXPAYMENTTYPE, OXLANG', 'oxorder', 'OXORDERNR', $sOrderNo);
         if (empty($aResult['OXPAYMENTTYPE'])) {
            $this->displayMessage(['message' => 'Order Reference not exist in Database!']);
            return false;
        }
        elseif ($aResult['OXPAYMENTTYPE'] !== 'novalnetpayments') {
              $this->displayMessage(['message' => 'Order Reference not matched!']);
              return false;
       }

        //Update old txn details to New format
        if (!empty($aDbValue) && $aResult['OXPAYMENTTYPE'] != 'novalnetpayments') {
            $aAdditionalData = unserialize($aDbValue['ADDITIONAL_DATA']);
            if (empty($aAdditionalData)) {
                $aAdditionalData = json_decode($aDbValue['ADDITIONAL_DATA'], true);
            }
            if (!isset($aAdditionalData['updated_old_txn_details']) && $aAdditionalData['updated_old_txn_details'] != true) {
                NovalnetUtil::convertOldTxnDetailsToNewFormat($this->eventData['transaction']['order_no']);
                $aDbValue = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'TID', $this->parentTID);
            }
        }

        if (empty($aDbValue)) {
            if ($this->eventData['event']['type'] == 'PAYMENT' || $this->eventData['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT') {
                if (!empty($this->parentTID) && (empty($this->eventData['transaction']['tid']))) {
                    $this->eventData['transaction']['tid'] = $this->parentTID;
                }
                // Handle communication failure
                $this->handleCommunicationFailure();
                if ($this->eventData['transaction']['payment_type'] == 'ONLINE_TRANSFER_CREDIT' && !empty($this->parentTID)) {
                    $amount = NovalnetUtil::formatCurrency($this->eventData['transaction']['amount'], $this->eventData['transaction']['currency']) . ' ' . $this->eventData['transaction']['currency'];
                    $this->aNovalnetComments[] = ['NOVALNET_CALLBACK_CREDIT' => [$this->eventData['event']['parent_tid'], $amount, NovalnetUtil::getFormatDate(), $this->eventData['transaction']['tid']]];
                    $this->aAdditionalData['novalnet_comments'][] = $this->aNovalnetComments;
                    $aUpdateDetails = ['ADDITIONAL_DATA' => json_encode($this->aAdditionalData), 'AMOUNT' => $this->eventData['transaction']['amount'], 'CREDITED_AMOUNT' => $this->eventData['transaction']['amount']];
                    NovalnetUtil::updateTableValues('novalnet_transaction_detail', $aUpdateDetails, 'TID', $this->parentTID);
                    NovalnetUtil::updateTableValues('oxorder', ['OXFOLDER' => 'ORDERFOLDER_NEW'], 'OXID', $this->eventData['custom']['inputval2']);
                }
                return false;
            } else {
                $this->displayMessage(['message' => 'event type mismatched for TID' . $this->parentTID]);
                return false;
            }
        }
        $aDbValue['LANG'] = $aResult['OXLANG'];
        $sOrderNo = (isset($aDbValue['ORDER_NO']) &&  !empty($aDbValue['ORDER_NO'])) ? $aDbValue['ORDER_NO'] : $this->eventData['transaction']['order_no'];
        if (!empty($this->eventData['transaction']['order_no']) && ($sOrderNo != $this->eventData['transaction']['order_no'])) {
            $this->displayMessage(['message' => 'Transaction mapping failed']);
            return false;
        }
        return $aDbValue;
    }

    /**
     * Display callback messages
     *
     * @param $sMessage
     *
     * @return null
     */
    protected function displayMessage($sMessage)
    {
        $this->_aViewData['sNovalnetMessage'] = $sMessage['message'];
    }

    /**
     * Send notification mail to the merchant
     *
     * @param array $data
     *
     * @return null
     */
    protected function sendNotifyMail($data)
    {
        $blCallbackMail = NovalnetUtil::getNovalnetConfigValue('blWebhookSendMail');
        if (!empty($blCallbackMail)) {
            $oMail = oxNew(\OxidEsales\Eshop\Core\Email::class);
            $sEmailSubject = 'Novalnet Callback Script Access Report ' . $this->orderID;
            $oShop = $oMail->getShop();
            $oMail->setFrom($oShop->oxshops__oxorderemail->value);
            $oMail->setSubject($sEmailSubject);
            $oMail->setBody($data);
            $oMail->setRecipient($blCallbackMail);
            if ($oMail->send()) {
                return 'Mail sent successfully<br>';
            }
        }
    }

    /**
     * Handle communication failure
     */
    protected function handleCommunicationFailure()
    {
        $aPaymentDetails = [];
        // Get shop details
        if (isset($this->eventData['transaction']['order_no']) && !empty($this->eventData['transaction']['order_no'])) {
            $aPaymentDetails = NovalnetUtil::getTableValues('OXPAYMENTTYPE, OXLANG, OXTOTALORDERSUM, OXUSERID, OXPAID', 'oxorder', 'OXORDERNR', $this->eventData['transaction']['order_no']);
        } else {
            $aPaymentDetails = NovalnetUtil::getTableValues('OXPAYMENTTYPE, OXLANG, OXTOTALORDERSUM, OXUSERID, OXPAID', 'oxorder', 'OXID', $this->eventData['custom']['inputval2']);
        }
        $sWord   = 'novalnet';
        if (!empty($aPaymentDetails['OXPAYMENTTYPE']) && strpos($aPaymentDetails['OXPAYMENTTYPE'], $sWord) !== false) {
            $this->oNovalnetSession = Registry::getSession();
            $bTestMode = ($this->eventData['transaction']['test_mode']);
            $aNovalnetComments = [];
            // Form transaction comments
            $aNovalnetComments = $this->formPaymentComments($bTestMode);
            $orderNo = '';
            if (empty($this->eventData['transaction']['order_no'])) {
                $orderNo = oxNew(Counter::class)->getNext('oxOrder');
                $aTransactionDetails = [
                        'transaction' => [
                            'tid'           => $this->parentTID,
                            'order_no'      => $orderNo
                        ]
                ];
                NovalnetUtil::doCurlRequest($aTransactionDetails, 'transaction/update');
            } else {
                $orderNo = $this->eventData['transaction']['order_no'];
            }
            $aOrderData = NovalnetUtil::getTableValues('*', 'novalnet_transaction_detail', 'ORDER_NO', $orderNo);
            $status = 'NOT_FINISHED';
            $sPayment = ((isset($this->eventData['custom']['input3']) && ($this->eventData['custom']['input3'] == 'paymentName') && !empty($this->eventData['custom']['inputval3'])) ? $this->eventData['custom']['inputval3'] : 'Novalnet');
            if (in_array($this->eventData['transaction']['status'], ['ON_HOLD', 'PENDING', 'CONFIRMED'])) {
                if (in_array($this->eventData['transaction']['status'], ['ON_HOLD','PENDING'])) {
                    $iCredited = 0;
                } else {
                    $iCredited = $this->eventData['transaction']['amount'];
                }
                $aAdditionalData['novalnet_comments'][] = $aNovalnetComments;

                if ($this->eventData['transaction']['amount'] == 0 && $this->eventData['transaction']['status'] === 'CONFIRMED' && (isset($this->eventData['custom']) && isset($this->eventData['custom']['input4']) && $this->eventData['custom']['input4'] == 'ZeroBooking')) {
                    $aNovalnetComments[] = ['NOVALNET_ZERO_BOOKING_TEXT' => [null]];
                    $aAdditionalData['zero_request_data'] = $this->eventData;
                    $aAdditionalData['zero_txn_reference'] = $this->eventData['transaction']['payment_data']['token'];
                    $aAdditionalData['zero_amount_booking'] = '1';
                }

                if (empty($aOrderData) && empty($aOrderData['ORDER_NO'])) {
                    $aAdditionalData['updated_old_txn_details'] = true;
                    $this->oDb->execute('INSERT INTO novalnet_transaction_detail (ORDER_NO, PAYMENT_TYPE, TID, AMOUNT, GATEWAY_STATUS, CREDITED_AMOUNT, ADDITIONAL_DATA) VALUES (?, ?, ?, ?, ? ,? ,?)', [$orderNo, $sPayment, $this->parentTID, $this->eventData['transaction']['amount'], $this->eventData['transaction']['status'], $iCredited, json_encode($aAdditionalData)]);
                } else {
                    NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['TID' => $this->parentTID, 'PAYMENT_TYPE' => $sPayment,'AMOUNT' => $this->eventData['transaction']['amount'], 'GATEWAY_STATUS' => $this->eventData['transaction']['status'],  'CREDITED_AMOUNT' => $iCredited, 'ADDITIONAL_DATA' => json_encode($aAdditionalData)], 'ORDER_NO', $orderNo);
                }

                // Set empty paid date for pending transaction status
                if (
                    in_array($this->eventData['transaction']['payment_type'], array('PAYPAL', 'PRZELEWY24', 'INVOICE', 'PREPAYMENT', 'MULTIBANCO'))
                    && $this->eventData['transaction']['status'] == 'PENDING'
                ) {
                    $sOrderStatus = '0000-00-00 00:00:00';
                } else { // Set paid date
                    $sOrderStatus = NovalnetUtil::getFormatDateTime();
                }

                if (
                    !in_array($this->eventData['transaction']['payment_type'], NovalnetUtil::$aUnPaidPayments)
                    && $this->eventData['transaction']['status'] == 'CONFIRMED' && $this->eventData['transaction']['amount'] != 0
                ) {
                    $status = 'OK';
                }
                if ($this->eventData['transaction']['status'] == 'PENDING' && $this->eventData['transaction']['payment_type'] == 'INVOICE') {
                    $status = 'OK';
                }
                $sComments = NovalnetUtil::getTranslateComments($aNovalnetComments, $aPaymentDetails['OXLANG']);
                NovalnetUtil::updateTableValues('oxorder', ['OXPAID' => $sOrderStatus, 'OXFOLDER' => 'ORDERFOLDER_NEW', 'OXTRANSSTATUS' => $status, 'OXORDERNR' => $orderNo], 'OXID', $this->eventData['custom']['inputval2']);
                $database = DatabaseProvider::getDb();
                $ids = $database->getCol('SELECT oxid FROM oxuserbaskets WHERE oxuserid = :oxuserid', [
                    ':oxuserid' => $aPaymentDetails['OXUSERID']
                ]);
                array_walk($ids, [$this, 'deleteItemById'], \OxidEsales\Eshop\Application\Model\UserBasket::class);
                $this->sendNotifyMail($sComments);
                $this->displayMessage(['message' => 'Novalnet Callback Script executed successfully, Transaction details are updated']);
            } elseif ($aPaymentDetails['OXPAID'] == '0000-00-00 00:00:00') {
                $aAdditionalData['novalnet_comments'][] = $aNovalnetComments;
                if (empty($aOrderData) && empty($aOrderData['ORDER_NO'])) {
                    $aAdditionalData['updated_old_txn_details'] = true;
                    $this->oDb->execute('INSERT INTO novalnet_transaction_detail (ORDER_NO, PAYMENT_TYPE, TID, AMOUNT, GATEWAY_STATUS, CREDITED_AMOUNT, ADDITIONAL_DATA) VALUES (?, ?, ?, ?, ? ,? ,?)', [$orderNo, $sPayment, $this->parentTID, $this->eventData['transaction']['amount'], $this->eventData['transaction']['status'], 0, json_encode($aAdditionalData)]);
                } else {
                    NovalnetUtil::updateTableValues('novalnet_transaction_detail', ['TID' => $this->parentTID, 'PAYMENT_TYPE' => $sPayment, 'AMOUNT' => $this->eventData['transaction']['amount'], 'GATEWAY_STATUS' => $this->eventData['transaction']['status'], 'ADDITIONAL_DATA' => json_encode($aAdditionalData)], 'ORDER_NO', $orderNo);
                }
                NovalnetUtil::updateTableValues('oxorder', ['OXFOLDER' => 'ORDER_STATE_PAYMENTERROR', 'OXTRANSSTATUS' => $status, 'OXORDERNR' => $orderNo], 'OXID', $this->eventData['custom']['inputval2']);

                NovalnetUtil::updateArticleStockFailureOrder($this->eventData['custom']['inputval2']);
                $this->displayMessage(['message' => 'Novalnet Callback Script executed successfully, Order no: ' . $orderNo]);
            }
        }
    }

    /**
     * Form transaction details
     *
     * @param integer $bTestMode
     *
     * @return string
     */
    public function formPaymentComments($iTestMode)
    {
        $this->aNovalnetComments = [];
        $this->aNovalnetComments[] = ['NOVALNET_TRANSACTION_ID' => [$this->eventData['transaction']['tid']]];
        if (!empty($iTestMode)) {
            $this->aNovalnetComments[] = ['NOVALNET_TEST_ORDER' => [null]];
        }
        if (!empty($this->eventData['transaction']['status_code']) && !in_array($this->eventData['transaction']['status_code'], array(75, 85, 86, 90, 91, 98, 99, 100))) { // Failure transaction
            $this->aNovalnetComments[] = ['NOVALNET_PAYMENT_FAILED' => [$this->eventData['result']['status_text']]];
        }
        return $this->aNovalnetComments;
    }

    public function deleteItemById($id, $key, $className)
    {
        /** @var \OxidEsales\Eshop\Core\Model\BaseModel $modelObject */
        $modelObject = oxNew($className);
        if ($modelObject->load($id)) {
            $modelObject->setIsDerived(false);
            $modelObject->delete();
        }
    }
}
