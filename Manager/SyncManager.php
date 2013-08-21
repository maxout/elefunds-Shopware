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

use Lfnds\Facade;
use Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;
use Lfnds\Exception\ElefundsCommunicationException;
use Shopware\CustomModels\Elefunds\Donation\Donation;

/**
 * Syncs between database and API.
 *
 * @package    elefunds Shopware Module
 * @subpackage Manager
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager
{

    /**
     * @var Facade
     */
    protected $facade;

    /**
     * @var array
     */
    protected $donationsToBeCancelled;

    /**
     * @var array
     */
    protected $donationsToBeCompleted;

    /**
     * Initialisation of the sync process.
     */
    public function __construct() {
         $this->facade = ConfigurationManager::getConfiguredFacade();
    }


    /**
     * Syncs all donations to the API.
     *
     * @return $this
     */
    public function syncDonations() {

        /** @var Shopware\CustomModels\Elefunds\Donation\Repository $donationRepository  */
        $donationRepository = Shopware()->Models()->getRepository(
            'Shopware\CustomModels\Elefunds\Donation\Donation'
        );

        $donationModels = $donationRepository->findSyncables();

        $this->donationsToBeCancelled = array();
        $this->donationsToBeCompleted = array();

        $donationsToBeAdded = array();
        $donationsWithPendingState = array();

        /** @var Shopware\CustomModels\Elefunds\Donation\Donation $donationModel */
        foreach ($donationModels as $donationModel) {

            switch ($donationModel->getState()) {

                case Donation::SCHEDULED_FOR_ADDING:
                    $donationsToBeAdded[$donationModel->getForeignId()] = $donationModel;
                    break;

                case Donation::SCHEDULED_FOR_CANCELLATION:
                    $this->donationsToBeCancelled[$donationModel->getForeignId()] = $donationModel;
                    break;

                case Donation::SCHEDULED_FOR_COMPLETION:
                    $this->donationsToBeCompleted[$donationModel->getForeignId()] = $donationModel;
                    break;

                case Donation::PENDING:
                    $donationsWithPendingState[$donationModel->getForeignId()] = $donationModel;
                    break;

            }
        }

        $this->filterPendingDonations($donationsWithPendingState);

        // Add pending donations
        try {
            $this->facade->addDonations($donationsToBeAdded);
            $donationRepository->setStates($donationsToBeAdded, Donation::PENDING);
        } catch (ElefundsCommunicationException $exception) {
            $donationRepository->setStates($donationsToBeAdded, Donation::SCHEDULED_FOR_ADDING);
        }

        // Cancel donations
        try {
            $this->facade->cancelDonations(array_keys($this->donationsToBeCancelled));
            $donationRepository->setStates($this->donationsToBeCancelled, Donation::CANCELLED);
        } catch (ElefundsCommunicationException $exception) {
            $donationRepository->setStates($this->donationsToBeCancelled, Donation::SCHEDULED_FOR_CANCELLATION);
        }

        // Verify donation
         try {
            $this->facade->completeDonations(array_keys($this->donationsToBeCompleted));
            $donationRepository->setStates($this->donationsToBeCompleted, Donation::COMPLETED);
        } catch (ElefundsCommunicationException $exception) {
            $donationRepository->setStates($this->donationsToBeCompleted, Donation::SCHEDULED_FOR_COMPLETION);
        }

        $donationRepository->persistAll();

        return $this;
    }


    /**
     * Allocates pending donations to the verification or cancellation queue if the corresponding order
     * state has changed.
     *
     * We can't hook into the model, as there are to many extensions (and even shopware itself),
     * that are using the deprecated sOrder->setOrderState() method, that does not necessarily trigger an
     * event.
     *
     * @param array $donationModels
     * @return array
     */
    protected function filterPendingDonations(array $donationModels) {

        if (count($donationModels) > 0) {
            $shopwareCancelledStates = ConfigurationManager::getInternal('states/cancelled');
            $shopwareCompletedStates = ConfigurationManager::getInternal('states/completed');

            $orderStatesSql = '
                SELECT ordernumber, status
                FROM s_order
                WHERE ordernumber IN (' . implode(',', array_keys($donationModels)) . ')
                AND status IN (' . implode(',', array_merge($shopwareCompletedStates, $shopwareCancelledStates)) . ')
            ';


            $orderStates = Shopware()->Db()->fetchAll($orderStatesSql);

            foreach ($orderStates as $orderState) {
                if (in_array((int)$orderState['status'], $shopwareCancelledStates)) {
                    $this->donationsToBeCancelled[(int)$orderState['ordernumber']] = $donationModels[(int)$orderState['ordernumber']];
                }
                if (in_array((int)$orderState['status'], $shopwareCompletedStates)) {
                    $this->donationsToBeCompleted[(int)$orderState['ordernumber']] = $donationModels[(int)$orderState['ordernumber']];
                }
            }
        }
    }

}
