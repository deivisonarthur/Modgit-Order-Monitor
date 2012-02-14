<?php
/**
 * Copyright (C) 2012 Slawomir Iwanczuk <slawomir@iwanczuk.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Mage_OrderMonitor_Helper_Customer
    extends Mage_Core_Helper_Abstract
{
    const XML_PATH_CANCEL_NEW     = 'ordermonitor/customer/cancel_new';
    const XML_PATH_CANCEL_PENDING = 'ordermonitor/customer/cancel_pending';
    const XML_PATH_CANCEL_STATUS  = 'ordermonitor/customer/cancel_status';

    public function canCancelNew($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CANCEL_NEW, $store);
    }

    public function canCancelPending($store = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CANCEL_PENDING, $store);
    }

    public function getCancelStatus($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_CANCEL_STATUS, $store);
    }

    public function canCancel(Mage_Sales_Model_Order $order)
    {
        if ($order->getCustomerId() != Mage::getSingleton('customer/session')->getCustomerId()) {
            return false;
        }

        if (!in_array($order->getState(), Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates(), $strict = true)) {
            return false;
        }

        if (!$order->canCancel() || $order->hasInvoices() || $order->hasShipments()) {
            return false;
        }

        if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW && $this->canCancelNew($order->getStore())) {
            return true;
        }

        if ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT && $this->canCancelPending($order->getStore())) {
            return true;
        }

        return false;
    }
}
