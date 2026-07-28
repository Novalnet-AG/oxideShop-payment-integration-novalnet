<?php

/**
 * Novalnet payment module
 *
 * This file is used for common and utility functions of
 * Novalnet payment module.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: NovalnetUtil.php
 */

namespace oe\novalnet\Core;

use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\Eshop\Core\UtilsServer;
use OxidEsales\EshopCommunity\Application\Model\Country;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Curl;
use OxidEsales\Eshop\Application\Model\DeliverySet;
/**
 * Class NovalnetUtil.
 */
class NovalnetUtil
{
    /**
      * Novalnet module version
      *
      * @var string
      */
    public static $sNovalnetVersion = '13.3.1';

    /**
     * Novalnet End Point
     *
     * @var string
     */
    public static $sEndPoint = 'https://payport.novalnet.de/v2/';

    public static $aUnPaidPayments = [
        'INVOICE', 'PREPAYMENT', 'MULTIBANCO'
    ];

    /**
     * Performs payment request for all payments and return response for direct payments
     *
     * @param object $oOrder
     *
     * @return mixed
     */
    public static function doPayment($oOrder)
    {
        $oSession = Registry::getSession();
        $oBasket = $oSession->getBasket();
        $aDynValue = array_map('trim', Registry::getSession()->getVariable('dynvalue'));
        if (empty($aDynValue)) {
            $aDynValue = Registry::getRequest()->getRequestParameter('dynvalue');
        }
        $aNovalnetPaymentDetails = html_entity_decode($aDynValue['novalnet_payment_details']);
        $aResponse = json_decode($aNovalnetPaymentDetails, true);

        $aRequest = self::importNovalnetBasicParams($oBasket, $oOrder, $aResponse);
        self::importPaymentDetails($aRequest, $oOrder, $aResponse);
        $oSession->setVariable('aNovalnetGatewayRequest', $aRequest);
        $sEndPoint = (!empty($aResponse['booking_details']['payment_action']) && $aResponse['booking_details']['payment_action'] == 'authorized') ? 'authorize' : 'payment';
        $aPaymentResponse = self::doCurlRequest($aRequest, $sEndPoint);

        if (!empty($aPaymentResponse['result']['status'])) {
            if ($aPaymentResponse['result']['status'] == 'SUCCESS') {
                if (!empty($aPaymentResponse['result']['redirect_url'])) {
                    $oSession->setVariable('sNovalnetTxnSecret', $aPaymentResponse['transaction']['txn_secret']);
                    Registry::getUtils()->redirect($aPaymentResponse['result']['redirect_url'], false);
                } else {
                    $oSession->setVariable('aNovalnetGatewayResponse', $aPaymentResponse);
                    return $aPaymentResponse;
                }
            } else {
                $oOrdrId  = $oOrder->oxorder__oxid->value;
                $oOrder = oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
                $oOrder->delete($oOrdrId);
                if ($oSession->getVariable('sess_challenge')) {
                    $oSession->deleteVariable('sess_challenge');
                }
                self::clearNovalnetRedirectSession();
                self::setNovalnetPaygateError($aPaymentResponse['result']);
            }
        }
    }


    /**
     * Performs cURL request
     *
     * @param mixed  $mxRequest
     * @param string $sUrl
     * @param string $sAccessKey
     *
     * @return mixed
     */
    public static function doCurlRequest($mxRequest, $sUrl, $sAccessKey = '')
    {
        $oRequest = json_encode($mxRequest);
        $sUrl = self::$sEndPoint . $sUrl;
        if (empty($sAccessKey)) {
            $sAccessKey = self::getNovalnetConfigValue('sPaymentAccessKey');
        }
        $sHeaders = array(
            "Content-Type:application/json",
            "charset:utf-8",
            "Accept:application/json",
            "X-NN-Access-Key:" . base64_encode($sAccessKey),
        );
        $oCurl = oxNew(Curl::class);
        $oCurl->setMethod('POST');
        $oCurl->setUrl($sUrl);
        $oCurl->setQuery($oRequest);
        $oCurl->setOption('CURLOPT_HTTPHEADER', $sHeaders);
        $oCurl->setOption('CURLOPT_FOLLOWLOCATION', 0);
        $oCurl->setOption('CURLOPT_POST', 1);
        $oCurl->setOption('CURLOPT_SSL_VERIFYHOST', false);
        $oCurl->setOption('CURLOPT_SSL_VERIFYPEER', false);
        $oCurl->setOption('CURLOPT_RETURNTRANSFER', 1);
        $mxData = $oCurl->execute();
        return json_decode($mxData, true);
    }

    /**
     * Get transaction data
     *
     * @param object $oBasket
     *
     * @return array
     */
    public static function getTransactionData($oBasket, $shippingcost)
    {
        $oTheme = oxNew('oxTheme');
        return [
            // Add Amount details.
            'amount'         => NovalnetUtil::getBasketDetails($oBasket, $shippingcost, true),
            'currency'       => $oBasket->getBasketCurrency()->name,
            // Add System details.
            'system_name'    => 'oxideshop',
            'system_version' => ShopVersion::getVersion() . '-NN' . self::$sNovalnetVersion . '-NNT' . $oTheme->getActiveThemeId(),
            'system_url'     => Registry::getConfig()->getShopMainUrl(),
            'system_ip'      => self::getIpAddress(true),
        ];
    }

    /**
     * Get basket details for wallet payment
     *
     * @param object $oBasket
     *
     * @return array
     */
    public static function getBasketDetails($oBasket, $shippingcost, $dAmount = false)
    {
        $articleDetails = [];
        $dTotalAmount = 0;
        $dDiscountAmount = 0;
        $sShippingSetId = Registry::getSession()->getVariable('sShipSet');
        $oDeliverySet = oxNew(DeliverySet::class);
        if ($oDeliverySet->load($sShippingSetId)) {
            $sShippingName = $oDeliverySet->oxdeliveryset__oxtitle->value;
        } else {
            $sShippingName = $sShippingSetId;
        }

        if (!empty($sShippingSetId)) {
            if ($dAmount) {
                $dTotalAmount += NovalnetUtil::formatCost($shippingcost);
            } else {
                $articleDetails[] = array('label' => $sShippingName, 'amount' => (string) NovalnetUtil::formatCost($shippingcost), 'type' => 'SUBTOTAL');
            }
        }
        foreach ($oBasket->getContents() as $basketItem) {
            $oPrice = $basketItem->getPrice();
            $amount = $oPrice->getBruttoPrice();
            if ($dAmount) {
                $dTotalAmount += NovalnetUtil::formatCost($amount);
            } else {
                $label = $basketItem->getTitle() . ' ' . $basketItem->getAmount() . ' x ' . Registry::getConfig()->getActShopCurrencyObject()->sign . sprintf('%0.2f', $amount);
                $articleDetails[] = array('label' => $label, 'amount' => (string) NovalnetUtil::formatCost($amount), 'type' => 'SUBTOTAL');
            }
        }
        if ($oBasket->getVoucherDiscount()->getBruttoPrice() > 0) {
            if ($dAmount) {
                $dDiscountAmount = NovalnetUtil::formatCost($oBasket->getVoucherDiscount()->getBruttoPrice());
            } else {
                $articleDetails[] = array('label' => 'discount', 'amount' => '-' . (string) NovalnetUtil::formatCost($oBasket->getVoucherDiscount()->getBruttoPrice()), 'type' => 'SUBTOTAL');
            }
        }
        $oWrappingCost = $oBasket->getWrappingCost();
        $oGiftCardCost = $oBasket->getCosts('oxgiftcard');
        $oPaymentCost = NovalnetUtil::getTableValues('OXADDSUM', 'oxpayments', 'OXID', 'novalnetpayments');
        if ($oPaymentCost['OXADDSUM'] != null && $oPaymentCost['OXADDSUM'] > 0) {
            if ($dAmount) {
                $dTotalAmount += NovalnetUtil::formatCost($oPaymentCost['OXADDSUM']);
            } else {
                $articleDetails[] = array('label' => 'surcharge payment method', 'amount' => (string)  NovalnetUtil::formatCost($oPaymentCost['OXADDSUM']), 'type' => 'SUBTOTAL');
            }
        }
        if ($oWrappingCost != null && $oWrappingCost->getBruttoPrice() > 0) {
            if ($dAmount) {
                $dTotalAmount += NovalnetUtil::formatCost($oWrappingCost->getBruttoPrice());
            } else {
                $articleDetails[] = array('label' => 'gift wrapping', 'amount' => (string) NovalnetUtil::formatCost($oWrappingCost->getBruttoPrice()), 'type' => 'SUBTOTAL');
            }
        }
        if ($oGiftCardCost != null && $oGiftCardCost->getBruttoPrice() > 0) {
            if ($dAmount) {
                $dTotalAmount += NovalnetUtil::formatCost($oGiftCardCost->getBruttoPrice());
            } else {
                $articleDetails[] = array('label' => 'gift card', 'amount' => (string) NovalnetUtil::formatCost($oGiftCardCost->getBruttoPrice()), 'type' => 'SUBTOTAL');
            }
        }
        if ($dAmount) {
            if ($dTotalAmount > 0) {
                $dTotalAmount = $dTotalAmount - $dDiscountAmount;
            }
            return $dTotalAmount;
        } else {
            return $articleDetails;
        }
    }

    /**
     * Imports transaction parameters
     *
     * @param object $oBasket
     * @param object $oOrder
     * @param string $aData
     *
     * @return array
     */
    public static function importNovalnetBasicParams($oBasket, $oOrder, $aData)
    {
        $aRequest = [];
        $aRequest['merchant'] = [
            'signature' => self::getNovalnetConfigValue('sProductActivationKey'),
            'tariff'    => self::getNovalnetConfigValue('sTariffId')
        ];
        // Form order details parameters.
        $aRequest['transaction'] = [
            'payment_type'  => $aData['payment_details']['type'],
            'test_mode'     => $aData['booking_details']['test_mode'],
            // Add Amount details.
            'currency'       => $oBasket->getBasketCurrency()->name,
            // Add System details.
            'system_name'    => 'oxideshop',
            'system_version' => ShopVersion::getVersion() . '-NN' . self::$sNovalnetVersion,
            'system_url'     => Registry::getConfig()->getShopMainUrl(),
            'system_ip'      => self::getIpAddress(true),
        ];
        if (isset($aData['booking_details']['payment_action']) && $aData['booking_details']['payment_action'] == 'zero_amount') {
            $aRequest['transaction']['amount'] = 0;
        } else {
            $aRequest['transaction']['amount'] = self::formatAmount($oBasket);
        }
        $oUser   = $oOrder->getOrderUser();
        $aRequest['customer'] = self::getCustomerData($oUser);
        $aRequest['custom']['lang'] = strtoupper(Registry::getLang()->getLanguageAbbr());
        $aRequest['custom']['input2']  = 'oxid_Id';
        $aRequest['custom']['inputval2'] = $oOrder->oxorder__oxid->value;
        $aRequest['custom']['input3']  = 'paymentName';
        $aRequest['custom']['inputval3'] = $aData['payment_details']['name'];
        if (isset($aData['booking_details']['payment_action']) && $aData['booking_details']['payment_action'] == 'zero_amount' && self::formatAmount($oBasket) > 0) {
            $aRequest ['custom']['input4'] = 'ZeroBooking';
            $aRequest ['custom']['inputval4'] = self::formatAmount($oBasket);
        }
        return $aRequest;
    }

    /**
     * Formats the date time
     *
     * @return string
     */
    public static function getFormatDateTime()
    {
        $utilsDate = Registry::getUtilsDate();
        return date('Y-m-d H:i:s', $utilsDate->getTime());
    }

    /**
     * Formats the date
     *
     * @return string
     */
    public static function getFormatDate()
    {
        $utilsDate = Registry::getUtilsDate();
        return date('Y-m-d', $utilsDate->getTime());
    }

    /**
     * Imports payment parameters
     *
     * @param  array  $aRequest
     * @param  object $oOrder
     * @param  array  $aData
     * @return array
     */
    public static function importPaymentDetails(&$aRequest, $oOrder, $aData)
    {
        $oUser   = $oOrder->getOrderUser();
        $oAddress = $oUser->getSelectedAddress();
        $sCompany = (!empty($oUser->oxuser__oxcompany->value) ? $oUser->oxuser__oxcompany->value : (!empty($oAddress->oxaddress__oxcompany->value) ? $oAddress->oxaddress__oxcompany->value : ''));
        if (!empty($aData['booking_details']['due_date'])) {
            $aRequest['transaction']['due_date'] = date('Y-m-d', strtotime('+' . $aData['booking_details']['due_date'] . ' days'));
        }
        if (!empty($aData['booking_details']['birth_date'])) {
            $aRequest['customer']['birth_date'] = $aData['booking_details']['birth_date'];
        } elseif (!empty($sCompany)) {
            $aRequest['customer']['billing']['company'] = $sCompany;
        }
        if (!empty($aRequest['customer']['birth_date']) && !empty($aRequest['customer']['billing']['company'])) {
            unset($aRequest['customer']['billing']['company']);
        }

        $aPaymentDataBookingDetails = ['iban', 'account_holder', 'bic', 'pan_hash', 'unique_id', 'wallet_token', 'routing_number', 'account_number'];
        $aTransactionBookingDetails = ['enforce_3d', 'create_token'];

        foreach ($aPaymentDataBookingDetails as $bookingDetail) {
            if (!empty($aData['booking_details'][$bookingDetail])) {
                $aRequest['transaction']['payment_data'][$bookingDetail] = $aData['booking_details'][$bookingDetail];
            }
        }
        foreach ($aTransactionBookingDetails as $bookingDetail) {
            if (! empty($aData['booking_details'][$bookingDetail])) {
                $aRequest['transaction'][$bookingDetail] = $aData['booking_details'][$bookingDetail];
            }
        }
        if (!empty($aData['booking_details']['mobile'])) {
            $aRequest['customer']['mobile'] = $aData['booking_details']['mobile'];
        }

        if (!empty($aData['booking_details']['cycle'])) {
            $aRequest['instalment'] = [
               'interval' => '1m',
               'cycles' => $aData['booking_details']['cycle'],
            ];
        }
        if (!empty($aData['booking_details']['payment_ref']['token'])) {
            $aRequest['transaction']['payment_data']['token'] = $aData['booking_details']['payment_ref']['token'];
        }
        if ($aData['payment_details']['type'] == 'PAYPAL') {
            $dPaymentCost = 0;
            if ($oOrder->oxorder__oxpaycost->value != 0) {
                $dPaymentCost = $oOrder->oxorder__oxpaycost->value;
            }

            $aRequest['cart_info'] = self::getCartInfo($dPaymentCost);
        }
        if ($aData['payment_details']['process_mode'] == 'redirect' || ($aData['payment_details']['process_mode'] == 'redirect' && isset($aData['booking_details']['do_redirect']) && $aData['booking_details']['do_redirect'] == 1)) {
            if (!empty(Registry::getSession()->getVariable('dNnOrderNo'))) {
                $aRequest['transaction']['order_no'] = Registry::getSession()->getVariable('dNnOrderNo');
            }
            self::importRedirectPaymentParameters($aRequest);
        }
    }

    /**
     * Import redirect payment details
     *
     * @param  $aRequest array data
     * @return null
     */
    public static function importRedirectPaymentParameters(&$aRequest)
    {
        $sReturnURL = htmlspecialchars_decode(Registry::getConfig()->getShopCurrentURL()) . 'cl=order&fnc=novalnetGatewayReturn&stoken=' . self::getRequestParameter('stoken');
        $aRequest['transaction']['return_url'] = $aRequest['transaction']['error_return_url'] = $sReturnURL;
        $aRequest['custom']['input1']     = 'shop_lang';
        $aRequest['custom']['inputval1']  = Registry::getLang()->getBaseLanguage();
    }
    /**
     * Import product details
     *
     * @param int $dPaymentCost
     *
     * @return array
     */
    public static function getCartInfo($dPaymentCost)
    {
        $articleDetails = [];
        $oBasket = Registry::getSession()->getBasket();
        $dDeliveryCost = $oBasket->getDeliveryCost();
        $dVatValue = 0;

        foreach ($oBasket->getContents() as $basketItem) {
            $oPrice = $basketItem->getUnitPrice();
            $oArticle = $basketItem->getArticle();
            if ($oPrice->isNettoMode()) {
                $amount = $oPrice->getNettoPrice();
                $dVatValue = $dVatValue +  $oPrice->getVatValue();
            } else {
                $amount = $oPrice->getBruttoPrice();
            }
            $articleDetails[] = array('name' => $basketItem->getTitle(), 'price' => (string) NovalnetUtil::formatCost($amount), 'quantity' => $basketItem->getAmount(), 'description' => $oArticle->oxarticles__oxshortdesc->value, 'category' => 'physical');
        }

        if ($dPaymentCost > 0) {
            $articleDetails[] = array('name' => 'surcharge payment method', 'price' => (string) NovalnetUtil::formatCost($dPaymentCost), 'quantity' => 1, 'description' => '', 'category' => 'physical');
        }

        if ($oBasket->getVoucherDiscount()->getBruttoPrice() > 0) {
            $articleDetails[] = array('name' => 'discount', 'price' => '-' . (string) NovalnetUtil::formatCost($oBasket->getVoucherDiscount()->getBruttoPrice()), 'quantity' => 1, 'description' => '', 'category' => 'physical');
        }

        $oWrappingCost = $oBasket->getWrappingCost();
        $oGiftCardCost = $oBasket->getCosts('oxgiftcard');
        if ($oWrappingCost != null && $oWrappingCost->getBruttoPrice() > 0) {
            $articleDetails[] = array('name' => 'gift wrapping', 'price' => (string) NovalnetUtil::formatCost($oWrappingCost->getBruttoPrice()), 'quantity' => 1, 'description' => '', 'category' => 'physical');
        }
        if ($oGiftCardCost != null && $oGiftCardCost->getBruttoPrice() > 0) {
            $articleDetails[] = array('name' => 'gift card', 'price' => (string) NovalnetUtil::formatCost($oGiftCardCost->getBruttoPrice()), 'quantity' => 1, 'description' => '', 'category' => 'physical');
        }

        if (!empty($oBasket->getProductVats()) && !empty($dVatValue)) {
            $dVatValue = 0;
            foreach ($oBasket->getProductVats() as $vat) {
                $dVatValue += str_replace(',', '.', $vat);
            }
        }

        return ['line_items' => $articleDetails, 'items_shipping_price' => NovalnetUtil::formatCost($dDeliveryCost->getBruttoPrice()), 'items_tax_price' => NovalnetUtil::formatCost($dVatValue)];
    }

    /**
     * Get the customer parameters.
     *
     * @param object $oUser
     *
     * @return array
     */
    public static function getCustomerData($oUser)
    {
        $aCustomer = [
          'first_name'  =>  self::setUTFEncode($oUser->oxuser__oxfname->value),
          'last_name'   =>  self::setUTFEncode($oUser->oxuser__oxlname->value),
          'email'       =>  $oUser->oxuser__oxusername->value,
        ];
        if (!empty($oUser->oxuser__oxmobfon->value)) {
            $aCustomer['mobile'] = $oUser->oxuser__oxmobfon->value;
        } elseif (!empty($aCustomer['mobile'] = $oUser->oxuser__oxprivfon->value)) {
            $aCustomer['mobile'] = $oUser->oxuser__oxprivfon->value;
        }
        if (!empty($oUser->oxuser__oxfon->value)) {
            $aCustomer['tel'] = $oUser->oxuser__oxfon->value;
        }
        if ($oUser->oxuser__oxbirthdate->value != '0000-00-00' && empty($oUser->oxuser__oxcompany->value)) {
            $aCustomer['birth_date'] = $oUser->oxuser__oxbirthdate->value;
        }
        $aCustomer ['customer_ip'] = self::getIpAddress();
        $aCustomer ['customer_no'] = $oUser->oxuser__oxcustnr->value;
        $aCustomer ['billing'] = [
                                    'street'       => self::setUTFEncode($oUser->oxuser__oxstreet->value),
                                    'city'         => self::setUTFEncode($oUser->oxuser__oxcity->value),
                                    'zip'          => $oUser->oxuser__oxzip->value,
                                    'country_code' => self::getCountryISO($oUser->oxuser__oxcountryid->value),
                                    'house_no'     => trim($oUser->oxuser__oxstreetnr->value)
                                ];
        if (!empty($oUser->oxuser__oxcompany->value)) {
            $aCustomer ['billing']['company'] = $oUser->oxuser__oxcompany->value;
        }
        $aCustomer['shipping'] = self::getAddress($oUser);
        if (!empty($oUser->oxuser__oxfax->value)) {
            $aCustomer['fax'] = $oUser->oxuser__oxfax->value;
        }
        if (!empty($oUser->oxuser__oxustid->value)) {
            $aCustomer['vat_id'] = $oUser->oxuser__oxustid->value;
        }
        return $aCustomer;
    }

    /**
     * Format the amount
     *
     * @param object $oBasket
     *
     * @return integer
     */
    public static function formatAmount($oBasket)
    {
        return str_replace(',', '', number_format($oBasket->getPrice()->getBruttoPrice(), 2)) * 100;
    }

    /**
     * Form instalment data
     *
     * @param array $aData
     * @param int $dAmount
     *
     * @return array
     */
    public static function formInstalmentData($aData, $dAmount = 0)
    {
        $aInstalmentDetails = [];
        if (!empty($aData['instalment'])) {
            $currency = Registry::getConfig()->getCurrencyObject($aData['transaction']['currency']);
            $aInstalment = $aData['instalment'];
            if (!empty($aInstalment['cycles_executed'])) {
                $aInstalmentDetails['instalment_cycle_amount'] = $aInstalment['cycle_amount'];
                $aInstalmentDetails['instalment_total_cycles'] = count($aInstalment['cycle_dates']);
                $aInstalmentDates = [];
                for ($iCycle = 1; $iCycle <= $aInstalmentDetails['instalment_total_cycles']; $iCycle++) {
                    $aInstalmentDetails['instalment' . $iCycle] = array();
                    if ($iCycle == 1) {
                        $aInstalmentDetails['instalment' . $iCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_COMPLETED';
                    } else {
                        $aInstalmentDetails['instalment' . $iCycle]['status'] = 'NOVALNET_INSTALMENT_STATUS_PENDING';
                    }
                    if (1 < $iCycle && $iCycle < $aInstalmentDetails ['instalment_total_cycles']) {
                        $aInstalmentDetails ['instalment' . $iCycle]['amount'] = self::formatCurrency($aInstalment['cycle_amount'], $aData['transaction']['currency']) . ' ' . $currency->sign;
                    } elseif ($iCycle == $aInstalmentDetails['instalment_total_cycles']) {
                        $dTotalAmount = !empty($dAmount) ? $dAmount : $aData['transaction']['amount'];
                        $last_cycle = $dTotalAmount - ($aInstalment['cycle_amount'] * ($iCycle - 1));
                        $aInstalmentDetails ['instalment' . $iCycle]['amount'] = self::formatCurrency($last_cycle, $aData['transaction']['currency']) . ' ' . $currency->sign;
                    }
                    if ($iCycle == 1) {
                        $aInstalmentDetails ['instalment' . $iCycle]['paid_amount'] = $aInstalment['cycle_amount'];
                    }
                    if (!empty($aInstalment['cycle_dates'][ $iCycle + 1 ])) {
                        $aInstalmentDates [] = $iCycle . '-' . $aInstalment['cycle_dates'][$iCycle + 1];
                        $aInstalmentDetails['instalment' . $iCycle ]['next_instalment_date'] = date('Y-m-d', strtotime($aInstalment['cycle_dates'][$iCycle + 1]));
                    }
                    // Put entry in which cycle execute
                    $aInstalmentDetails['instalment' . $aInstalment['cycles_executed']]['tid']                  = $aData['transaction']['tid'];
                    $aInstalmentDetails['instalment' . $aInstalment['cycles_executed']]['paid_date']            = date('Y-m-d', strtotime($aInstalment['cycle_dates'][$aInstalment['cycles_executed']]));

                    foreach (array('instalment_cycles_executed' => 'cycles_executed','due_instalment_cycles' => 'pending_cycles','amount' => 'cycle_amount') as $key => $value) {
                        if (!empty($aInstalment[$value])) {
                            if ($key == 'amount') {
                                $amount = self::formatCurrency($aInstalment[$value], $aData['transaction']['currency']) . ' ' . $currency->sign;
                                $aInstalmentDetails['instalment' . $aInstalment['cycles_executed']][$key] = $amount;
                            } else {
                                $aInstalmentDetails['instalment' . $aInstalment['cycles_executed']][$key] = $aInstalment[$value];
                            }
                            $aInstalmentDetails[$key] = $aInstalment[$value];
                        }
                    }
                }
                $aInstalmentDetails['future_instalment_dates'] = implode('|', $aInstalmentDates);
            }
        }
        return $aInstalmentDetails;
    }

    /**
     * Get Fomatted amount based on the currency.
     *
     * @param int $dAmount
     * @param string $sCurrency
     *
     * @return int
     */
    public static function formatCurrency($iAmount, $sCurrency)
    {
        return Registry::getLang()->formatCurrency($iAmount / 100, Registry::getConfig()->getCurrencyObject($sCurrency));
    }

    /**
     * Convert given amount to cents
     *
     * @param int $dCost
     *
     * @return int
     */
    public static function formatCost($dCost)
    {
        return round(sprintf('%0.2f', $dCost) * 100);
    }
    /**
     * Check TID valid
     *
     * @param int $dTid
     *
     * @return boolean
     */
    public static function validTid($dTid)
    {
        return preg_match('/^\d{17}$/', $dTid);
    }

    /**
     * Get Address data.
     *
     * @param object $oUser
     *
     * @return array
     */
    public static function getAddress($oUser)
    {
        $oAddress = $oUser->getSelectedAddress();
        $bShipping = Registry::getSession()->getVariable('blshowshipaddress');
        if ($bShipping == 0) {
            $aAddress['same_as_billing'] = 1;
        } else {
            $aAddress = [
                'first_name'   => $oAddress->oxaddress__oxfname->value,
                'last_name'    => $oAddress->oxaddress__oxlname->value,
                'street'       => self::setUTFEncode($oAddress->oxaddress__oxstreet->value),
                'city'         => self::setUTFEncode($oAddress->oxaddress__oxcity->value),
                'zip'          => $oAddress->oxaddress__oxzip->value,
                'country_code' => self::getCountryISO($oAddress->oxaddress__oxcountryid->value),
                'house_no'     => trim($oAddress->oxaddress__oxstreetnr->value),
            ];
        }
        return $aAddress;
    }

    /**
     * Get Novalnet configuration value
     *
     * @param string $sConfig
     *
     * @return string
     */
    public static function getNovalnetConfigValue($sConfig)
    {
        $config = Registry::getConfig();
        return $config->getConfigParam($sConfig);
    }

    /**
     * Get country ISO code
     *
     * @param string $sCountryId
     *
     * @return string
     */
    public static function getCountryISO($sCountryId)
    {
        $oCountry = oxNew(\OxidEsales\Eshop\Application\Model\Country::class);
        $oCountry->load($sCountryId);
        return $oCountry->oxcountry__oxisoalpha2->value;
    }

    /**
     * Set the UTF8 encoding
     *
     * @param string $sStr
     *
     * @return string
     */
    public static function setUTFEncode($sStr)
    {
        return (mb_detect_encoding($sStr, 'UTF-8', true) === false) ? utf8_encode($sStr) : $sStr;
    }

    /**
     * Get Server / Remote IP address
     *
     * @param boolean $blServer
     *
     * @return string
     */
    public static function getIpAddress($blServer = false)
    {
        if (empty($blServer)) {
            $oUtilsServer = oxNew(UtilsServer::class);
            return $oUtilsServer->getRemoteAddress();
        } else {
            if (empty($_SERVER['SERVER_ADDR'])) {
                // Handled for IIS server
                return gethostbyname($_SERVER['SERVER_NAME']);
            } else {
                return $_SERVER['SERVER_ADDR'];
            }
        }
    }

    public static function checkWebhookIp(string $novalnetHostIp)
    {
        $ipKeys = ['HTTP_X_FORWARDED_HOST', 'HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                if (in_array($key, ['HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_FOR'])) {
                    $forwardedIps = (!empty($_SERVER[$key])) ? explode(",", $_SERVER[$key]) : [];
                    if (in_array($novalnetHostIp, $forwardedIps)) {
                        return true;
                    }
                }

                if ($_SERVER[$key] ==  $novalnetHostIp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Sets error message from the failure response of Novalnet
     *
     * @param array $aResponse
     *
     * @return string
     */
    public static function setNovalnetPaygateError($aResponse)
    {
        Registry::getUtils()->redirect(sprintf("%sindex.php?type=error&cl=payment&payerror=-50&payerrortext=%s", Registry::getConfig()->getShopSecureHomeURL(), $aResponse['status_text']), true, 302);
    }

    /**
     * Forms bank details for Invoice and Prepayment orders
     *
     * @param  array $aInvoiceDetails
     * @return array
     */
    public static function getInvoiceComments($aInvoiceDetails)
    {
        $aNovalnetComments = [];
        $aInvoiceBankDetails = $aInvoiceDetails['transaction']['bank_details'];
        $dAmount = ($aInvoiceDetails['transaction']['payment_type'] == 'INSTALMENT_INVOICE') ? $aInvoiceDetails['instalment']['cycle_amount'] : $aInvoiceDetails['transaction']['amount'];
        $sFormattedAmount = self::formatCurrency($dAmount, $aInvoiceDetails['transaction']['currency']) . ' ' . $aInvoiceDetails['transaction']['currency'];
        if ($aInvoiceDetails['transaction']['status_code'] != '75') {
            if ($aInvoiceDetails['transaction']['status'] != 'ON_HOLD') {
                if ($aInvoiceDetails['transaction']['payment_type'] == 'INSTALMENT_INVOICE' && !empty($aInvoiceDetails['transaction']['due_date'])) {
                    $aNovalnetComments[] = ['NOVALNET_INSTALMENT_INVOICE_BANK_DESC_WITH_DUE' => [$sFormattedAmount, date('d.m.Y', strtotime($aInvoiceDetails['transaction']['due_date']))]];
                } else {
                    $aNovalnetComments[] = ['NOVALNET_INVOICE_BANK_DESC_WITH_DUE' => [$sFormattedAmount, date('d.m.Y', strtotime($aInvoiceDetails['transaction']['due_date']))]];
                }
            } else {
                if ($aInvoiceDetails['transaction']['payment_type'] == 'INSTALMENT_INVOICE') {
                    $aNovalnetComments[] = ['NOVALNET_INSTALMENT_INVOICE_BANK_DESC' => [$sFormattedAmount]];
                } else {
                    $aNovalnetComments[] = ['NOVALNET_INVOICE_BANK_DESC' => [$sFormattedAmount]];
                }
            }
        }
        $aNovalnetComments[] = ['NOVALNET_ACCOUNT' => [$aInvoiceBankDetails['account_holder']]];
        $aNovalnetComments[] = ['NOVALNET_IBAN'    => [$aInvoiceBankDetails['iban']]];
        $aNovalnetComments[] = ['NOVALNET_BIC' => [$aInvoiceBankDetails['bic']]];
        $aNovalnetComments[] = ['NOVALNET_BANK' => [$aInvoiceBankDetails['bank_name']]];
        $aNovalnetComments[] = ['NOVALNET_PLACE' => [$aInvoiceBankDetails['bank_place']]];
        $aNovalnetComments[] = ['NOVALNET_INVOICE_MULTI_REF_DESCRIPTION' => [null]];
        $aNovalnetComments[] = ['NOVALNET_PAYMENT_REFERENCE_1' => [$aInvoiceDetails['transaction']['tid']]];
        if (!empty($aInvoiceDetails['transaction']['invoice_ref'])) {
            $aNovalnetComments[] = ['NOVALNET_PAYMENT_REFERENCE_2' => [$aInvoiceDetails['transaction']['invoice_ref']]];
        }

        // Add the QR code in the bank details
        if (!empty($aInvoiceDetails['transaction']['bank_details']['qr_image'])) {
            $aNovalnetComments[] = ['NOVALNET_PAYMENT_QR_CODE_REFERENCE' => []];
            $aNovalnetComments[] = ['NOVALNET_PAYMENT_QR_CODE_IMAGE' => [$aInvoiceDetails['transaction']['bank_details']['qr_image']]];
        }

        return $aNovalnetComments;
    }

    /**
     * Set redirection URL while any invalid conceptual during payment process
     *
     * @param string $sMessage
     *
     * @return string
     */
    public static function setRedirectURL($sMessage)
    {
        return Registry::getConfig()->getSslShopUrl() . 'index.php?cl=payment&payerror=-1&payerrortext=' . urlencode(self::setUTFEncode($sMessage));
    }

    /**
     * Clears Novalnet session
     *
     * @return null
     */
    public static function clearNovalnetSession()
    {
        $oSession = Registry::getSession();
        $aNovalnetSessions = ['aNovalnetGatewayRequest', 'aNovalnetGatewayResponse', 'dynvalue'];
        foreach ($aNovalnetSessions as $sSession) {
            if ($oSession->hasVariable($sSession)) {
                $oSession->deleteVariable($sSession);
            }
        }
    }
    /**
     * Update article quantity
     *
     * @param int $dOrderId
     *
     * @return boolean
     */
    public static function updateArticleStockFailureOrder($dOrderId)
    {
        // Get oxorderarticles details
        $db = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $moduleConfigsQuery = "SELECT * FROM oxorderarticles where OXORDERID = :OXORDERID";
        $aOxorderArticles = $db->getAll($moduleConfigsQuery, [
            ':OXORDERID' => $dOrderId
        ]);
        foreach ($aOxorderArticles as $aOxorderArticle) {
            self::updateStock($aOxorderArticle['OXARTID'], $aOxorderArticle['OXAMOUNT']);
        }
        return true;
    }

    /**
     * Get the product quantity and update the quantity in oxarticles table
     *
     * @param string  $oxArtID
     * @param integer $oxAmount
     *
     * @return null
     */
    public static function updateStock($oxArtID, $oxAmount)
    {
        $dgetArtCount = self::getTableValues('OXSTOCK', 'oxarticles', 'OXID', $oxArtID);
        $dProductId = $dgetArtCount['OXSTOCK'] + $oxAmount;
        if ($dProductId < 0) {
            $dProductId = 0;
        }
        // Stock updated in oxarticles table
        self::updateTableValues('oxarticles', ['OXSTOCK' => $dProductId], 'OXID', $oxArtID);
    }

    /**
     * Clear Novalnet redirect session values
     *
     * @return null
     */
    public static function clearNovalnetRedirectSession()
    {
        $oSession = Registry::getSession();
        $aNovalnetSessions = [
            'oUser', 'oBasket', 'oUserPayment','dNnOrderNo', 'blSave', 'aOrderArticles', 'sNovalnetProcessMode', 'sNovalnetPaymentName','sPaymentId'
        ];
        foreach ($aNovalnetSessions as $sSession) {
            if ($oSession->hasVariable($sSession)) {
                $oSession->deleteVariable($sSession);
            }
        }
    }

    /**
     * Returns the request parameter value
     *
     * @param string $data
     *
     * @return string
     */
    public static function getRequestParameter($data = '')
    {
        return Registry::getConfig()->getRequestParameter($data);
    }

    /**
     * Returns the stored value from the novalnet table
     *
     * @param integer $iOrderNo
     *
     * @return array
     */
    public static function getAdditionalData($iOrderNo)
    {
        $aAdditionalData  = self::getTableValues('ADDITIONAL_DATA', 'novalnet_transaction_detail', 'ORDER_NO', $iOrderNo);
        return json_decode($aAdditionalData['ADDITIONAL_DATA'], true);
    }

    /**
     * Convert novalnet old order details to new format
     *
     * @param  integer $iOrderNo
     * @param  array   $aTransDetails
     * @return null
     */
    public static function convertOldTxnDetailsToNewFormat($iOrderNo, $aTransDetails = [])
    {
        $aOrderTable = self::getTableValues('OXPAYMENTTYPE, NOVALNETCOMMENTS ', 'oxorder', 'OXORDERNR', $iOrderNo);
        if (!empty($aTransDetails)) {
            $aTxnTable = $aTransDetails;
        } else {
            $aTxnTable = self::getTableValues('PAYMENT_ID, GATEWAY_STATUS, AMOUNT, REFUND_AMOUNT, CREDITED_AMOUNT, ADDITIONAL_DATA, ZERO_TRXNDETAILS, ZERO_TRXNREFERENCE, ZERO_TRANSACTION', 'novalnet_transaction_detail', 'ORDER_NO', $iOrderNo);
        }
        $aCallBackTable = self::getTableValues('SUM(AMOUNT) AS PAID_AMOUNT', 'novalnet_callback_history', 'ORDER_NO', $iOrderNo);
        $dCreditedAmount = 0;
        //Get the old orderpayment method
        $sPaymentName = self::getNovalnetPaymentName($aOrderTable['OXPAYMENTTYPE']);
        //Get the old order novalnet comments
        $sComments = $aOrderTable['NOVALNETCOMMENTS'];
        //Get the old order refund amount
        $dRefundAmount = $aTxnTable['REFUND_AMOUNT'];
        $dTotalAmount = $aTxnTable['AMOUNT'];
        if ($aTxnTable['AMOUNT'] != $aTxnTable['CREDITED_AMOUNT']) {
            $dTotalAmount = $aTxnTable['CREDITED_AMOUNT'];
        }
        //Get the old order Credited amount
        if (in_array($aTxnTable['PAYMENT_ID'], ['40', '41'])) {
            $dCreditedAmount = $dTotalAmount;
        } elseif (in_array($aTxnTable['PAYMENT_ID'], ['34', '6', '37', '78']) && $aTxnTable['GATEWAY_STATUS'] == '100' && empty($aCallBackTable['PAID_AMOUNT'])) {
            $dCreditedAmount = $dTotalAmount;
        } elseif (!empty($aCallBackTable['PAID_AMOUNT'])) {
            $dCreditedAmount = $aCallBackTable['PAID_AMOUNT'];
        }

        if (!empty($aTxnTable['ADDITIONAL_DATA'])) {
            $aOldAdditionalData = unserialize($aTxnTable['ADDITIONAL_DATA']);
            //Get old invoice payment bank details
            if (in_array($aTxnTable['PAYMENT_ID'], ['27', '41'])) {
                $aBankComments = [];
                if (in_array($aTxnTable['GATEWAY_STATUS'], ['98', '99', '91', '85'])) {
                    $aBankComments[] = ['NOVALNET_INVOICE_BANK_DESC' => [$aTxnTable['AMOUNT']]];
                }
                $aBankComments[] = ['NOVALNET_ACCOUNT' => [$aOldAdditionalData['invoice_account_holder']]];
                $aBankComments[] = ['NOVALNET_IBAN' => [$aOldAdditionalData['invoice_iban']]];
                $aBankComments[] = ['NOVALNET_BIC' => [$aOldAdditionalData['invoice_bic']]];
                $aBankComments[] = ['NOVALNET_BANK' => [$aOldAdditionalData['invoice_bankname'], $aOldAdditionalData['invoice_bankplace']]];
                $aBankComments[] = ['NOVALNET_PLACE' => [$aOldAdditionalData['invoice_bankplace']]];
                $aBankComments[] = ['NOVALNET_INVOICE_MULTI_REF_DESCRIPTION' => [null]];
                $aBankComments[] = ['NOVALNET_PAYMENT_REFERENCE_1' => [$aOldAdditionalData['tid']]];
            }
        }
        $aNewAdditionalData = [];
        if (!empty($aBankComments)) {
            $aNewAdditionalData['bank_details'] = $aBankComments;
        }
        if (!empty($sComments)) {
            $aNewAdditionalData['old_novalnet_comments'] = $sComments;
        }
        $aNewAdditionalData['updated_old_txn_details'] = true;
        if ($aTxnTable['ZERO_TRANSACTION'] == '1') {
            $aNewAdditionalData['zero_amount_booking'] = $aTxnTable['ZERO_TRANSACTION'];
            $aNewAdditionalData['zero_txn_reference'] = $aTxnTable['ZERO_TRXNREFERENCE'];
            $aNewAdditionalData['zero_request_data'] = unserialize($aTxnTable['ZERO_TRXNDETAILS']);
        }
        if ($dCreditedAmount != 0) {
            $dCreditedAmount = $dCreditedAmount - $dRefundAmount;
        }
        $sGatewayStatus = $aTxnTable['GATEWAY_STATUS'];
        if (in_array($aTxnTable['GATEWAY_STATUS'], ['98', '99', '91', '85'])) {
            $sGatewayStatus = 'ON_HOLD';
        } elseif (in_array($aTxnTable['GATEWAY_STATUS'], ['86', '90', '75'])) {
            $sGatewayStatus = 'PENDING';
        } elseif ($aTxnTable['GATEWAY_STATUS'] == '100') {
            $sGatewayStatus = 'CONFIRMED';
        } elseif ($aTxnTable['GATEWAY_STATUS'] == '103') {
            $sGatewayStatus = 'DEACTIVATED';
        }
        self::updateTableValues('novalnet_transaction_detail', ['AMOUNT' => $dTotalAmount, 'PAYMENT_TYPE' => $sPaymentName, 'ADDITIONAL_DATA' => json_encode($aNewAdditionalData), 'CREDITED_AMOUNT' => $dCreditedAmount, 'GATEWAY_STATUS' => $sGatewayStatus], 'ORDER_NO', $iOrderNo);
    }

    /**
     * Get novalnet transaction comments
     *
     * @param integer $iOrderNo
     *
     * @return String
     */
    public static function getNovalnetTransactionComments($iOrderNo, $backend = false)
    {
        $aAdditionalData = self::getAdditionalData($iOrderNo);
        $sComments = '';
        if (!empty($aAdditionalData)) {
            if (!empty($aAdditionalData['old_novalnet_comments'])) {
                $sComments .= $aAdditionalData['old_novalnet_comments'];
            }
            if (!empty($aAdditionalData['novalnet_comments'])) {
                foreach ($aAdditionalData['novalnet_comments'] as $key => $value) {
                    if ($backend) {
                        foreach ($value as $index => $item) {
                            foreach (['NOVALNET_PAYMENT_QR_CODE_IMAGE', 'NOVALNET_PAYMENT_QR_CODE_REFERENCE'] as $removeKey) {
                                if (isset($value[$index][$removeKey])) {
                                    unset($value[$index]);
                                }
                            }
                        }
                    }
                    $sComments .= NovalnetUtil::getTranslateComments($value);
                }
            }
        }
        return $sComments;
    }


    /**
     * Gets Novalnet payment name for given order
     *
     * @param string $sPaymentType
     *
     * @return string
     */
    public static function getNovalnetPaymentName($sPaymentType)
    {
        $oPayment = oxNew(Payment::class);
        $oPayment->load($sPaymentType);
        return $oPayment->oxpayments__oxdesc->rawValue;
    }

    /**
    * Gets Novalnet payment name for given order
    *
    * @param string $sPayment
    *
    * @return boolean
    */
    public static function checkNovalnetPayment($sPayment)
    {
        return preg_match("/novalnet/i", $sPayment);
    }

    /**
     * Check the value is number or not
     *
     * @return boolean
     */
    public static function isValidDigit($input)
    {
        return (bool)(preg_match('/^[0-9]+$/', $input));
    }

    /**
     * Get Translated text
     *
     * @param  array  $aComments
     * @param  string $oOrderLang
     * @return array
     */
    public static function getTranslateComments($aComments, $oOrderLang = '')
    {
        $aTranslateText = '';
        if (!empty($aComments)) {
            $oLang = Registry::getLang();
            if (!empty($oOrderLang)) {
                $oLang->setBaseLanguage($oOrderLang);
            }
            foreach ($aComments as $aKey => $aArray) {
                if (is_array($aArray)) {
                    foreach ($aArray as $sLangText => $sLangValue) {
                        if ($sLangText === 'NOVALNET_CALLBACK_INSTALMENT_MESSAGE') {
                            $count = substr_count($oLang->translateString($sLangText), "%s");
                            if ($count != count($sLangValue)) {
                                $sLangValue = array($sLangValue[0], $sLangValue[2], $sLangValue[1], date('Y-m-d H:i:s'));
                            }
                        }
                        $aTranslateText .= vsprintf($oLang->translateString($sLangText), $sLangValue);
                    }
                }
            }
        }
        return $aTranslateText;
    }



    /**
     * Get the table values
     *
     * @param string $sColumn
     * @param string $sTable
     * @param string $sCondition
     * @param string $sConditionValue
     *
     * @return string
     */
    public static function getTableValues($sColumn, $sTable, $sCondition, $sConditionValue)
    {
        $sQuery = "SELECT  $sColumn  FROM  $sTable  WHERE  $sCondition  = ? LIMIT 1 ";
        return DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getRow($sQuery, array($sConditionValue));
    }
    /**
     * Update the table values
     *
     * @param string $sTable
     * @param array  $aData
     * @param string $sCondition
     * @param string $sConditionValue
     *
     * @return null
     */
    public static function updateTableValues($sTable, $aData, $sCondition, $sConditionValue)
    {
        $sUpdateColumn = implode(' =  ?, ', array_keys($aData));
        $sUpdateColumn .= ' = ? ';
        $aUpdateValues = array_values($aData);
        array_push($aUpdateValues, $sConditionValue);
        $sQuery = "UPDATE  $sTable  SET  $sUpdateColumn WHERE $sCondition = ? ";
        DatabaseProvider::getDb()->execute($sQuery, $aUpdateValues);
    }

    /**
     * Returns the request parameter value
     *
     * @param string $sFirstname
     *
     * @return string
     */
    public static function getSalByFirstname($sFirstname)
    {
        $sQuery = "SELECT oxsal FROM oxuser WHERE oxfname = ? LIMIT 1";
        return DatabaseProvider::getDb()->getOne($sQuery, array($sFirstname));
    }
}
