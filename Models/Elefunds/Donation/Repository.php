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

namespace Shopware\CustomModels\Elefunds\Donation;
use DateTime;
use Shopware\Components\Model\ModelRepository;

use \Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;

/**
 * The donation repository.
 *
 * @package    elefunds Shopware Module
 * @subpackage Models
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <contact@elefunds>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Repository extends ModelRepository
{

    /**
     * Receives all donations that have a potential for syncing.
     *
     * @return array of donations
     */
    public function findSyncables() {

        $builder = $this->getEntityManager()->createQueryBuilder();

        $observationTime = new DateTime(
            sprintf('-%d days', ConfigurationManager::getInternal('donations/daysToLookForPending'))
        );

        $builder->select('donation')
                ->from('Shopware\CustomModels\Elefunds\Donation\Donation', 'donation')

                ->where('donation.state = :scheduledForAdding')
                ->orWhere('donation.state = :scheduledForCancellation')
                ->orWhere('donation.state = :scheduledForCompletion')
                ->orWhere('(donation.state = :pending AND donation.time > :observationTime)')

                ->setParameter('scheduledForAdding', Donation::SCHEDULED_FOR_ADDING)
                ->setParameter('scheduledForCancellation', Donation::SCHEDULED_FOR_CANCELLATION)
                ->setParameter('scheduledForCompletion', Donation::SCHEDULED_FOR_COMPLETION)
                ->setParameter('pending', Donation::PENDING)
                ->setParameter('observationTime', $observationTime->format('Y-m-d H:i:s'));

        return $builder->getQuery()->execute();
    }

    /**
     * Sets given donations as synced and flushes the
     * entity manager.
     *
     * @param array $donations
     * @param int $state
     * @return void
     */
    public function setStates(array $donations, $state) {
        foreach ($donations as $donation) {
            /** @var Donation $donation */
            $donation->setState($state);
            $this->getEntityManager()->persist($donation);
        }

    }

    /**
     * Saves all pending entities.
     *
     * @return void
     */
    public function persistAll() {
        $this->getEntityManager()->flush();
    }

}