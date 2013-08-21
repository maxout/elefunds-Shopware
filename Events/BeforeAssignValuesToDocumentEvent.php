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

use \Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager as LocaleManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;

/**
 * @package    Events
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Events_BeforeAssignValuesToDocumentEvent {

    /**
     * Adds the obligatory additional invoice text to a receipt with a donation.
     *
     * We do remove the donation from shipping notes.
     *
     * @param Enlight_Hook_HookArgs $args
     * @return void
     */
    public function execute(Enlight_Hook_HookArgs $args) {

        $additionalInvoiceText = LocaleManager::getLocale('additionalInvoiceText');

        /** @var Shopware_Components_Document $document */
        $document = $args->getSubject();
        $positions = $document->_view->getTemplateVars('Pages');

        $documentType = $document->_view->getTemplateVars('Document');
        $isShippingNote = $documentType['template'] === ConfigurationManager::getInternal('documents/shippingTemplate');

        foreach ($positions[0] as $key => $position) {

            if (strpos($position['articleordernumber'], 'ELEFUNDS-DONATION') !== FALSE) {
                if ($isShippingNote) {
                    // Remove donation from rendered document and update total ...
                    $order = $document->_view->getTemplateVars('Order');
                    $order['_amount'] = $order['_amount'] - $position['amount_netto'];
                    $order['_amountNetto'] = $order['_amountNetto'] - $position['amount_netto'];
                    $document->_view->assign('Order', $order);

                    unset($positions[0][$key]);
                    $positions[0] = array_values($positions[0]);
                    $document->_view->assign('Pages', $positions);

                } else {
                    // Add additional invoice text ...
                    $container = $document->_view->getTemplateVars('Containers');
                    if (isset($container['Content_Info']['value'])) {
                        $container['Content_Info']['value'] .= '<p>' . $additionalInvoiceText . '</p>';
                        $document->_view->assign('Containers', $container);
                    }

                    // Since tax is calculated for existing articles and not virtual ones, we add it here:
                    $positions[0][$key]['tax'] = 0;
                    $positions[0] = array_values($positions[0]);
                    $document->_view->assign('Pages', $positions);
                }

                break;
            }
        }
    }
}