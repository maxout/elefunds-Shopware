<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012-2013, elefunds GmbH <contact@elefunds>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of the elefunds GmbH nor the names of its
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

use Shopware\CustomModels\Elefunds\Donation\Donation;
use Shopware\Models\Order\Detail;
use \Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager as SyncManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;

/**
 * Cancels the donation when it's removed from an order in the backend.
 *
 * @package    Events
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <contact@elefunds>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.1
 */
class Shopware_Plugins_Frontend_LfndsDonation_Events_OrderChangedEvent {

    /**
     * @var Enlight_Controller_Request_RequestHttp
     */
    protected $request;

    /**
     * @var Shopware\CustomModels\Elefunds\Donation\Repository
     */
    protected $donationRepository;

    /**
     * Cancels the donation when it's removed from an order in the backend.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function execute(Enlight_Controller_EventArgs $args) {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $this->request = $subject->Request();

        $action =  strtolower($this->request->getActionName());

        // We have to check if the request is set to dispatch
        // https://github.com/ShopwareAG/shopware-4/pull/58
        if ($this->request->isDispatched()) {

            /** @var Shopware\CustomModels\Elefunds\Donation\Repository $donationRepository  */
            $this->donationRepository = Shopware()->Models()->getRepository(
                'Shopware\CustomModels\Elefunds\Donation\Donation'
            );

            if ($action === 'deleteposition') {
                $this->onPositionIsRemovedFromOrder();
            }
            if ($action === 'batchprocess') {
                $this->onBatchProcessOrders();
            }

            if ($action === 'save') {
                $this->onSaveOrder();
            }
        }
    }

    /**
     * Cancels a donation if it is removed from the order.
     */
    protected function onPositionIsRemovedFromOrder() {
        $positions = $this->request->getParam('positions', array(array('id' => $this->request->getParam('id'))));
        $orderId = $this->request->getParam('orderID', null);

        if (empty($positions) || empty($orderId)) {
            return;
        }
        /** @var Shopware\Models\Order\Order $order  */
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId);

        if ($order === NULL) {
            return;
        }

        $donationsToBeCancelled = array();
        foreach ($positions as $position) {

            if (empty($position['id'])) {
                continue;
            }

            $model = Shopware()->Models()->find('Shopware\Models\Order\Detail', $position['id']);

            //check if the model was founded.
            if ($model instanceof Detail) {
                if ($model->getArticleNumber() === 'ELEFUNDS-DONATION') {

                    /** @var Shopware\CustomModels\Elefunds\Donation\Donation $donation  */
                    $donation = $this->donationRepository->findOneBy(array('foreignId' => $order->getNumber()));
                    if ($donation !== NULL) {
                        $donationsToBeCancelled[] = $donation;
                    }
                }
            }
        }

        $this->donationRepository->setStates($donationsToBeCancelled, Donation::SCHEDULED_FOR_CANCELLATION);
        $this->sync();
    }

    /**
     * Checks wether donations need to be cancelled upon batch requests.
     */
    protected  function onBatchProcessOrders() {
        $orders = $this->request->getParam('orders', array(0 => $this->request->getParams()));

        $requiresSync = FALSE;
        foreach($orders as $key => $data) {
            if (empty($data) || empty($data['id'])) {
                continue;
            }
            if($this->isProcessedOrderDonation($data['id'], $data['status'])) {
                $requiresSync = TRUE;
            };
        }
        if ($requiresSync) {
            $this->donationRepository->persistAll();
            $this->sync();
        }
    }

    /**
     * Checks if the given order contains a donation and updates it's status.
     *
     * Returns true, if that is the case.
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    protected function isProcessedOrderDonation($id, $status) {

        if (empty($id) || empty($status)) {
            return FALSE;
        }
         /** @var \Shopware\Models\Order\Order $order */
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($id);
        if ($order === NULL) {
            return FALSE;
        }
        $originalState = $order->getOrderStatus();

        // The state did not change
        if ($originalState->getId() === (int)$status) {
            return FALSE;
        }

        $donationState = NULL;
        if (in_array($status, ConfigurationManager::getInternal('states/completed'))) {
            $donationState = Donation::SCHEDULED_FOR_COMPLETION;
        }
        if (in_array($status, ConfigurationManager::getInternal('states/cancelled'))) {
            $donationState = Donation::SCHEDULED_FOR_CANCELLATION;
        }

        if ($donationState !== NULL) {

            /** Shopware\CustomModels\Elefunds\Donation\Donation $donation */
            $donation = $this->donationRepository->findOneBy(array('foreignId' => $order->getNumber()));

            if ($donation === NULL) {
                return FALSE;
            }

            $this->donationRepository->setStates(array($donation), $donationState);
            return TRUE;
        }

        return FALSE;
    }


    /**
     * Checks if a saved order has a status update that needs to be reported.
     */
    protected function onSaveOrder() {
        $id = $this->request->getParam('id', NULL);
        $status = $this->request->getParam('status', NULL);

        if ($this->isProcessedOrderDonation($id, $status)) {
            $this->donationRepository->persistAll();
            $this->sync();
        };
    }


    /**
     * Invokes the API sync for the changed orders.
     */
    protected function sync() {
        $syncManager = new SyncManager();
        $syncManager->syncDonations();
    }

}