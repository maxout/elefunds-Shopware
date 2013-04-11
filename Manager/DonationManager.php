<?php

/**
 * elefunds Shopware Module
 *
 * Copyright (c) 2012, elefunds GmbH <hello@elefunds.de>.
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

/**
 * Manages the donation process on the checkout.
 *
 * @package    elefunds Shopware Module
 * @subpackage Manager
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Manager_DonationManager
{

    /**
     * @var int
     */
    protected $roundup;

    /**
     * @var array
     */
    protected $receivers;

    /**
     * The available receiver ids
     *
     * @var array
     */
    protected $availableReceiverIds;

    /**
     * @var string
     */
    protected $languageCode;

    /**
     * @var int
     */
    protected $grandTotal;

    /**
     * @var int
     */
    protected $suggestedRoundUp;

    /**
     * @var array
     */
    protected $userData;

    /**
     * @var array
     */
    protected $mockedDonation;

    /**
     * Initializes all necessary information to process the donation.
     *
     * @param int $roundup
     * @param array $receiverIds                                                           ~
     * @param int $grandTotal
     * @param int $suggestedRoundUp
     * @param array $userData
     */
    public function __construct($roundup, array $receiverIds, $grandTotal, $suggestedRoundUp = 0, array $userData = array()) {
        $this->roundup = $roundup;
        $this->grandTotal = $grandTotal;
        $this->suggestedRoundUp = $suggestedRoundUp;
        $this->userData = $userData;

        $this->languageCode = Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::getLanguage();

        /** @var Shopware\CustomModels\Elefunds\Receiver\Repository $receiverRepository  */
        $receiverRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Elefunds\Receiver\Receiver');
        $availableReceivers = $receiverRepository->getValidReceivers($this->languageCode);

        $this->availableReceiverIds = array();
        $this->receivers = array();

        /** @var Shopware\CustomModels\Elefunds\Receiver\Receiver $availableReceiver */
        foreach ($availableReceivers as $availableReceiver) {
            $this->availableReceiverIds[] = $availableReceiver->getId();
            if (in_array($availableReceiver->getId(), $receiverIds)) {
                $this->receivers[$availableReceiver->getId()] = $availableReceiver->getName();
            }
        }
    }

    /**
     * Returns a formatted string of the receivers chosen during the current donation
     *
     * E.g.: 'WWF, Greenpeace & Plan'
     *
     * @return string
     */
    public function getReceiversAsString() {

        $num = count($this->receivers);

        $conjunctionText = '';
        if ($num === 1) {
            $conjunctionText = array_shift(array_values($this->receivers));;
        } else {
            $i = 0;
            foreach ($this->receivers as $receiver) {

                if ($i < $num - 2) {
                    $conjunctionText .= $receiver . ', ';
                } else if ($i === $num - 2) {
                    $conjunctionText .= $receiver . ' & ';
                } else {
                    $conjunctionText .= $receiver;
                }

                ++$i;
            }
        }

        return $conjunctionText;
    }


    /**
     * All receivers that are qualified for a donation
     * with the receiver id as key and their name as value.
     *
     * @return array
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Returns the name of the donation
     *
     * @return string
     */
    public function getArticleName() {
        return Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::getLocale('donationTitle', $this->languageCode);
    }

    /**
     * Returns the suggested round up in cent
     *
     * @return int
     */
    public function getSuggestedRoundUp() {
        return $this->suggestedRoundUp;
    }

    /**
     * Returns the user data if available (or an empty array).
     *
     * @return array
     */
    public function getUserData() {
        return $this->userData;
    }

    /**
     * Returns the language code for the made donation.
     *
     * @return string
     */
    public function getLanguageCode() {
        return $this->languageCode;
    }

    /**
     * The roundup that was made in the donation.
     *
     * @param bool $asFloat
     * @return mixed
     */
    public function getRoundup($asFloat = FALSE)
    {
        if ($asFloat) {
            return round($this->roundup / 100, 2);
        }
        return $this->roundup;
    }

    /**
     * The grand total before the roundup.
     *
     * @param bool $asFloat
     * @return mixed
     */
    public function getGrandTotal($asFloat = FALSE)
    {
        if ($asFloat) {
            return round($this->grandTotal / 100, 2);
        }
        return $this->grandTotal;
    }

    /**
     * Returns all selected available receivers.
     *
     * @return array
     */
    public function getAvailableReceiverIds() {
        return $this->availableReceiverIds;
    }


    /**
     * Mocks the donation in an array and mimics
     * the basket.
     *
     * @param array $additionalParameters
     * @return array
     */
    public function getMockedDonation(array $additionalParameters = array()) {
        if ($this->mockedDonation === NULL) {

            $decimalSeparator = $this->languageCode === 'de' ? ',' : '.';

            $this->mockedDonation = array(
                'articleID'           =>  0,
                'ordernumber'         => 'ELEFUNDS-DONATION',
                'articlename'         =>  $this->getArticleName(),
                'modus'               =>  999, // use a special mode that doesn't exist already
                'receivers'           =>  $this->getReceiversAsString(),
                'amount'              =>  number_format($this->getRoundup(TRUE), 2, $decimalSeparator, '.'),
                'netprice'            =>  (string)$this->getRoundup(TRUE),
                'price'               =>  number_format($this->getRoundup(TRUE), 2, $decimalSeparator, '.'),
                'price_numeric'       =>  $this->getRoundup(TRUE),
                'priceNumeric'        =>  $this->getRoundup(TRUE),
                'quantity'            =>  '1',
                'tax'                 =>  '0',
                'taxID'               =>  '0',
                'tax_rate'            =>  '0',
                'esdarticle'          =>  '0',
                'image'               =>  array(
                    'src'   =>  array(
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_30.png',
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_57.png',
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_105.png',
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_140.png',
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_255.png',
                        'https://e9d42764b9c5b6e4f8a5-1c454ef842b6544cfc5c0d520d1538d7.ssl.cf1.rackcdn.com/elefunds_600.png'
                    )
                ),
                'donation'           => TRUE,
                'linkDetails'       => 'http://elefunds.de' // link to the shop's info page about elefunds or just elefunds.de
            );
        }

        $this->mockedDonation = array_merge($this->mockedDonation, $additionalParameters);
        return $this->mockedDonation;
    }
}