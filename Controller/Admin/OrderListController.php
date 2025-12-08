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

/**
 * Class OrderListController.
 */
class OrderListController extends OrderListController_parent
{
    /**
     * Override to filter out specific orders (e.g., order number 0)
     */
    protected function _prepareWhereQuery($sqlWhere, $fullQuery)
    {
        // Get default WHERE conditions
        $query = parent::_prepareWhereQuery($sqlWhere, $fullQuery);
        // Add your own custom filter
        $query .= " AND NOT(oxorder.oxordernr = 0 AND oxorder.oxpaymenttype = 'novalnetpayments') ";
        return $query;
    }
}
