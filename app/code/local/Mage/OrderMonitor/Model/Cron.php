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

class Mage_OrderMonitor_Model_Cron
{
    const XML_PATH_CANCEL_PENDING = 'ordermonitor/cron/cancel_pending';
    const XML_PATH_CANCEL_AFTER   = 'ordermonitor/cron/cancel_after';
    const XML_PATH_CANCEL_STATUS  = 'ordermonitor/cron/cancel_status';

    public function run()
    {
        $orders = Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('state', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);

        foreach ($orders as $order) {
            if (!Mage::getStoreConfigFlag(self::XML_PATH_CANCEL_PENDING, $order->getStoreId())) {
                continue;
            }

            if (!intval(Mage::getStoreConfig(self::XML_PATH_CANCEL_AFTER, $order->getStoreId()))) {
                continue;
            }

            if (!$order->canCancel() || $order->hasInvoices() || $order->hasShipments()) {
                continue;
            }

            if (strtotime(Varien_Date::now()) - strtotime($order->getCreatedAt()) < Mage::getStoreConfig(self::XML_PATH_CANCEL_AFTER, $order->getStoreId()) * 60) {
                continue;
            }

            try {
                $order->cancel();

                if ($status = Mage::getStoreConfig(self::XML_PATH_CANCEL_STATUS, $order->getStoreId())) {
                    $order->addStatusHistoryComment('', $status)
                          ->setIsCustomerNotified(null);
                }

                $order->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
    }
}
