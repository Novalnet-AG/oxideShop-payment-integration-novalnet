<?php

/**
 * Novalnet payment module
 *
 * This file is used for handling activate and deactivate events.
 *
 * @author    Novalnet AG
 * @copyright Copyright by Novalnet
 * @link      https://www.novalnet.de
 * @license   https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 *
 * Script: Events.php
 */

namespace oe\novalnet\Core;

use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Field;

/**
 * Class Events.
 */
class Events
{
    /**
     * Executes action on activate event
     *
     * @return null
     */
    public static function onActivate()
    {
        if (!empty($_REQUEST['fnc']) && !preg_match("/activate/i", $_REQUEST['fnc'])) {
            return;
        }
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class);
        self::addNovalnetTables(); // Creates Novalnet tables if not exists
        self::alterNovalnetColumns($oDbMetaDataHandler); // alters shop table and adds new field to manage the Novalnet comments
        self::addNovalnetPaymentMethods(); // inserts Novalnet payment methods
    }

    /**
     * Executes action on deactivate event
     *
     * @return null
     */
    public static function onDeactivate()
    {
        if (!empty($_REQUEST['fnc']) && !preg_match("/deactivate/i", $_REQUEST['fnc'])) {
            return;
        }
        $oPayment = oxNew(Payment::class);
        $oDb = DatabaseProvider::getDb();
        $aNovalnetConfig = ['sProductActivationKey', 'sPaymentAccessKey', 'sTariffId', 'sWebhooksUrl', 'blWebhookNotification', 'blWebhookSendMail'];
        foreach ($aNovalnetConfig as $name) {
            \OxidEsales\Eshop\Core\Registry::getConfig()->setConfigParam($name, null);
            $oDb->execute('DELETE FROM oxconfig where OXVARNAME = ?', [$name]);
        }
        // Deactivates the payment while uninstalling our module
        if ($oPayment->load('novalnetpayments')) {
            $oPayment->oxpayments__oxactive = new Field(0);
            $oPayment->save();
        }
    }

    /**
     * Add Novalnet column to shop table for storing Novalnet comments
     *
     * @param oxDbMetaDataHandler
     *
     * @return null
     */
    public static function alterNovalnetColumns($oDbMetaDataHandler)
    {
        $oDb = DatabaseProvider::getDb();

        if ($oDbMetaDataHandler->fieldExists('MASKED_DETAILS', 'novalnet_transaction_detail')) {
            $oDb->execute('ALTER TABLE novalnet_transaction_detail DROP MASKED_DETAILS, DROP CUSTOMER_ID, DROP REFERENCE_TRANSACTION');
        }
        if ($oDbMetaDataHandler->fieldExists('GATEWAY_STATUS', 'novalnet_transaction_detail')) {
            $oDb->execute('ALTER TABLE novalnet_transaction_detail MODIFY GATEWAY_STATUS varchar(30)');
        }
        if ($oDbMetaDataHandler->fieldExists('PAYMENT_TYPE', 'novalnet_transaction_detail')) {
            $oDb->execute('ALTER TABLE novalnet_transaction_detail MODIFY PAYMENT_TYPE varchar(225)');
        }
        if ($oDbMetaDataHandler->fieldExists('TOTAL_AMOUNT', 'novalnet_transaction_detail')) {
            $oDb->execute('ALTER TABLE novalnet_transaction_detail CHANGE TOTAL_AMOUNT CREDITED_AMOUNT int(20)');
        }
    }

    /**
     * Add Novalnet payment method
     *
     * @return null
     */
    public static function addNovalnetPaymentMethods()
    {
        $aPayments = [
            'novalnetpayments'  => [
                'OXID'          => 'novalnetpayments',
                'OXDESC_DE'     => 'Novalnet',
                'OXDESC_EN'     => 'Novalnet',
                'OXLONGDESC_DE' => 'Novalnet',
                'OXLONGDESC_EN' => 'Novalnet',
                'OXSORT'        => '1'
            ],
        ];
        $oLangArray = \OxidEsales\Eshop\Core\Registry::getLang()->getLanguageArray();
        $oPayment = oxNew(Payment::class);
        foreach ($oLangArray as $oLang) {
            foreach ($aPayments as $aPayment) {
                $oPayment->setId($aPayment['OXID']);
                $oPayment->setLanguage($oLang->id);
                $sLangAbbr = in_array($oLang->abbr, ['de', 'en']) ? $oLang->abbr : 'en';
                $oPayment->oxpayments__oxid          = new Field($aPayment['OXID']);
                $oPayment->oxpayments__oxtoamount    = new Field('1000000');
                $oPayment->oxpayments__oxaddsumrules = new Field('31');
                $oPayment->oxpayments__oxtspaymentid = new Field('');
                $oPayment->oxpayments__oxdesc     = new Field($aPayment['OXDESC_' . strtoupper($sLangAbbr)]);
                $oPayment->oxpayments__oxlongdesc = new Field($aPayment['OXLONGDESC_' . strtoupper($sLangAbbr)]);
                $oPayment->oxpayments__oxsort = new Field($aPayment['OXSORT']);
                $oPayment->save();
            }
        }
    }

    /**
     * Executes queries for creating Novalnet table
     *
     * @return null
     */
    public static function addNovalnetTables()
    {
        $oDb  = DatabaseProvider::getDb();
        $sSql = 'CREATE TABLE IF NOT EXISTS novalnet_transaction_detail (
                ID int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",
                PAYMENT_TYPE varchar(225) COMMENT "Executed payment type of this order",
                TID bigint(20) COMMENT "Novalnet transaction reference ID",
                ORDER_NO int(11) unsigned COMMENT "Order ID from shop",
                AMOUNT int(11) DEFAULT "0" COMMENT "Transaction amount",
                GATEWAY_STATUS varchar(30) NULL COMMENT "Novalnet transaction status",
                CREDITED_AMOUNT int(11) DEFAULT "0" COMMENT "Transaction credited the amount",
                ADDITIONAL_DATA TEXT DEFAULT NULL COMMENT "Stored Novalnet bank account details",
                PRIMARY KEY (ID),
                KEY TID (TID),
                KEY ORDER_NO (ORDER_NO)
                ) COMMENT="Novalnet Transaction History"';
        $oDb->execute($sSql);
    }
}
