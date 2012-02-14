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

class Mage_OrderMonitor_OrderController
    extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        $action = $this->getRequest()->getActionName();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    public function cancelAction()
    {
        $order = Mage::getModel('sales/order')->load(
            $this->getRequest()->getParam('order_id')
        );

        if ($order->getId()) {
            if (Mage::helper('ordermonitor/customer')->canCancel($order)) {
                try {
                    $order->cancel();

                    if ($status = Mage::helper('ordermonitor/customer')->getCancelStatus($store)) {
                        $order->addStatusHistoryComment('', $status)
                              ->setIsCustomerNotified(null);
                    }

                    $order->save();

                    Mage::getSingleton('catalog/session')
                        ->addSuccess($this->__('Your order has been canceled.'));
                } catch (Exception $e) {
                    Mage::getSingleton('catalog/session')
                        ->addException($e, $this->__('Cannot cancel your order.'));
                }
            } else {
                Mage::getSingleton('catalog/session')
                    ->addError($this->__('Cannot cancel your order.'));
            }

            $this->_redirect('sales/order/history');

            return;
        }

        $this->_forward('noRoute');
    }
}
