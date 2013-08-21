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
require_once __DIR__ . '/../SDK/Lfnds/Template/Shop/Helper/RequestHelper.php';

use Lfnds\Template\Shop\Helper\RequestHelper;
use \Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager as LocaleManager;

/**
 * @package    Events
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Events_PreDispatchCheckoutEvent  {

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * Executes the pre dispatch checkout event.
     *
     * @param Enlight_Controller_EventArgs $args
     */
    public function execute(Enlight_Controller_EventArgs $args) {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();

        $action =  strtolower($request->getActionName());

        if ($action === 'payment' || $action === 'finish') {
            $this->requestHelper = new RequestHelper();
            $this->prepareDonation($request);
        }
    }

    /**
     * Prepares the donation based uppn the request and pushes the relevant data into the session.
     *
     * @param Enlight_Controller_Request_RequestHttp $request
     * @return void
     */
    protected function prepareDonation(Enlight_Controller_Request_RequestHttp $request) {


        if ($this->requestHelper->isActiveAndValid()) {

            /** +++ Input Validation +++ */
            $session = Shopware()->Session();

            $roundup  = $this->requestHelper->getRoundUpAsFloatedString();

            // We have to cast the amount to string and then to int, as the session does not care about
            // floating point precision.
            $grandTotal = (int)(string)($session['sOrderVariables']['sAmount'] * 100);

            if ($this->requestHelper->isDonationReceiptRequested()) {
                $user = $session['sOrderVariables']['sUserData'];
                $userData = array(
                    'firstName'      =>  (string)$user['billingaddress']['firstname'],
                    'lastName'       =>  (string)$user['billingaddress']['lastname'],
                    'email'          =>  (string)$user['additional']['user']['email'],
                    'streetAddress'  =>  (string)$user['billingaddress']['street'] . ' ' . (string)$user['billingaddress']['streetnumber'],
                    'zip'            =>  (string)$user['billingaddress']['zipcode'],
                    'city'           =>  (string)$user['billingaddress']['city'],
                );

            } else {
                $userData = array();
            }
            /** ^^^ Input Validation ^^^ */

            /** +++ Basket Manipulation +++ */
            $basket = Shopware()->Session()->sOrderVariables['sBasket'];

            $basket['sAmount'] = $basket['sAmount'] + $roundup;
            $basket['content'][] = $this->getMockedDonation();

            $amount = (float)str_replace(',', '.', $basket['Amount']) + $roundup;
            $basket['Amount'] = number_format($amount, 2, ',', '.');

            $amountNet = (float)str_replace(',', '.', $basket['AmountNet']) + $roundup;
            $basket['AmountNet'] = number_format($amountNet, 2, ',', '.');

            $basket['AmountNumeric'] = $basket['AmountNumeric'] + $roundup;
            $basket['AmountNetNumeric'] = $basket['AmountNetNumeric'] + $roundup;

            if(!empty($basket['AmountWithTax'])) {
                $amountWithTax = (float)str_replace(',', '.', $basket['AmountWithTax']) + $roundup;
                $basket['AmountWithTax'] = number_format($amountWithTax, 2, ',', '.');
            }

            if(!empty($basket['AmountWithTaxNumeric'])) {
                $basket['AmountWithTaxNumeric'] = $basket['AmountWithTaxNumeric'] + $roundup;
            }

            Shopware()->Session()->sOrderVariables['sBasket'] = $basket;
            /** ^^^ Basket Manipulation ^^^ */

            // Write elefunds data to session.
            Shopware()->Session()->Elefunds = array(
                'receivers'          => $this->requestHelper->getReceiverIds(),
                'amount'             => $basket['sAmount'],
                'roundUp'            => $this->requestHelper->getRoundUp(),
                'grandTotal'         => $grandTotal,
                'availableReceivers' => $this->requestHelper->getAvailableReceiverIds(),
                'suggestedRoundUp'   => $this->requestHelper->getSuggestedRoundUp(),
                'userData'           => $userData,
            );


        }
    }

    /**
     * Creates a mocked view-article, to be injected into the order.
     *
     * @return array
     */
    protected function getMockedDonation() {
        $decimalSeparator = LocaleManager::getLocale('decimalSeparator');
        $roundupAsFloat = $this->requestHelper->getRoundUpAsFloatedString();

        return array(
            'articleID'           =>  0,
            'ordernumber'         => 'ELEFUNDS-DONATION',
            'articlename'         =>  LocaleManager::getLocale('donationTitle'),
            'modus'               =>  999, // use a special mode that doesn't exist already
            'receivers'           =>  $this->requestHelper->getReceiversAsString(),
            'amount'              =>  number_format($roundupAsFloat, 2, $decimalSeparator, '.'),
            'netprice'            =>  (string)$roundupAsFloat,
            'price'               =>  number_format($roundupAsFloat, 2, $decimalSeparator, '.'),
            'price_numeric'       =>  $roundupAsFloat,
            'priceNumeric'        =>  $roundupAsFloat,
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

}