<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012-2013, elefunds GmbH <hello@elefunds.de>.
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

/**
 * @package    Events
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Events_PositionRemovedFromOrderEvent {

    /**
     * Cancels the donation when it's removed from an order in the backend.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function execute(Enlight_Controller_EventArgs $args) {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();

        $action =  strtolower($request->getActionName());

        // @todo remove this once fixed in core
        // We have to check if the request is set to dispatch
        // https://github.com/ShopwareAG/shopware-4/pull/58
        if ($action === 'deleteposition' && $request->isDispatched()) {
            $positions = $request->getParam('positions', array(array('id' => $request->getParam('id'))));
            $orderId = $request->getParam('orderID', null);

            if (empty($positions) || empty($orderId)) {
                return;
            }
            /** @var Shopware\Models\Order\Order $order  */
            $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($orderId);

            /** @var Shopware\CustomModels\Elefunds\Donation\Repository $donationRepository  */
            $donationRepository = Shopware()->Models()->getRepository(
                'Shopware\CustomModels\Elefunds\Donation\Donation'
            );

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
                        $donation = $donationRepository->findOneBy(array('foreignId' => $order->getNumber()));
                        if ($donation !== NULL) {
                            $donationsToBeCancelled[] = $donation;
                        }
                    }
                }
            }

            $donationRepository->setStates($donationsToBeCancelled, Donation::SCHEDULED_FOR_CANCELLATION);

            $syncManager = new SyncManager();
            $syncManager->syncDonations();
        }
    }

}