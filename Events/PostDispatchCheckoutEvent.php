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

require_once __DIR__ . '/../SDK/Lfnds/Model/Donation.php';

use Shopware\CustomModels\Elefunds\Donation\Donation;
use \Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager as LocaleManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager as ConfigurationManager;
use \Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager as SyncManager;


/**
 * Hook into the checkout process.
 *
 * @package    Events
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Events_PostDispatchCheckoutEvent {

    /**
     * Env should contain the 'path' to the application.
     *
     * @var array
     */
    protected $env;

    /**
     * Executes the given event.
     *
     * @param Enlight_Controller_EventArgs $args
     * @param array $env
     */
    public function execute(Enlight_Controller_EventArgs $args, array $env) {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();
        $view = $subject->View();

        $this->env = $env;

        $action =  strtolower($request->getActionName());

        // @todo remove this once fixed in core
        // We have to check if the request is set to dispatch
        // https://github.com/ShopwareAG/shopware-4/pull/58
        if ($action === 'confirm' && $request->isDispatched()) {
            $this->injectDonationModule($view);
        }

        if ($action === 'finish'  && $request->isDispatched()) {
            $this->processDonation($view);
        }
    }

    /**
     * Calculates the donation, collects all necessary information and pushes them into the donation model.
     *
     * Prepares as well the output and the facebook share.
     *
     * @param Enlight_View_Default $view
     * @return void
     */
    protected function processDonation(Enlight_View_Default $view)
    {

        $session = Shopware()->Session();

        if (isset($session->Elefunds)) {
            $foreignId = $view->getAssign('sOrderNumber');

            $donation = new Donation();
            $donation->setForeignId($foreignId)
                     ->setAmount((int)$session->Elefunds['roundUp'])
                     ->setGrandTotal((int)$session->Elefunds['grandTotal'])
                     ->setReceiverIds($session->Elefunds['receivers'])
                     ->setAvailableReceiverIds($session->Elefunds['availableReceivers'])
                     ->setSuggestedAmount((int)$session->Elefunds['suggestedRoundUp'])
                     ->setDonator(
                         $session->Elefunds['userData']['email'],
                         $session->Elefunds['userData']['firstName'],
                         $session->Elefunds['userData']['lastName'],
                         $session->Elefunds['userData']['streetAddress'],
                         $session->Elefunds['userData']['zip'],
                         $session->Elefunds['userData']['city'],
                         LocaleManager::getLanguage()
                     )
                     ->setTime(new DateTime());

            Shopware()->Models()->persist($donation);
            Shopware()->Models()->flush();


            Shopware()->Template()->addTemplateDir(
                $this->env['path'] . 'Views/', 'elefunds'
            );

            $facade = ConfigurationManager::getConfiguredFacade(TRUE);
            $includeFacebookShare = ConfigurationManager::getInternal('share/includeSocialMediaShare');

            if($includeFacebookShare) {
                $facade->getConfiguration()->getView()
                    ->assign('foreignId', $view->getAssign('sOrderNumber'));
            }


            $view->sAmount = $session->Elefunds['amount'];
            $view->elefundsCss = $includeFacebookShare ? $facade->getPrintableCssTagStrings() : '';
            $view->elefundsJs = $includeFacebookShare ? $facade->getPrintableJavascriptTagStrings() : '';
            $additionalCss = ConfigurationManager::getInternal('module/additionalCss/success');

            if (!empty($additionalCss)) {
                $view->elefundsAdditionalCss = $additionalCss;
                $view->elefundsHasAdditionalCss = TRUE;
            } else {
                $view->elefundsHasAdditionalCss = FALSE;
            }

            $view->elefundsFacebookShare = $includeFacebookShare ? $facade->renderTemplate() : '';

            $view->extendsTemplate('checkout.tpl');
            $view->extendsTemplate('checkout_finish.tpl');

            $syncManager = new SyncManager();
            $syncManager->syncDonations();

            unset(Shopware()->Session()->Elefunds);
        }
    }

    /**
     * Injects the donation module into the checkout process.
     *
     * Don't worry, though. If disaster strikes and elefunds is not available,
     * we fallback to not displaying the module.
     *
     * @param Enlight_View_Default $view
     * @return void
     */
    protected function injectDonationModule(Enlight_View_Default $view)
    {
        $session = Shopware()->Session();
        $paymentProviderId = $session['sOrderVariables']['sPayment']['id'];

        if (ConfigurationManager::isAcceptedPaymentProvider($paymentProviderId)) {

            Shopware()->Template()->addTemplateDir(
                $this->env['path'] . 'Views/', 'elefunds'
            );

            $facade = ConfigurationManager::getConfiguredFacade();

            $position = ConfigurationManager::get('elefundsPosition');
            $position = in_array($position, array('bottom', 'top')) ? $position : 'bottom';
            $positionTemplate = sprintf('checkout_%s.tpl', $position);

            $facade->getConfiguration()->getView()->assign('sumExcludingDonation', $view->sAmount * 100);

            $view->elefunds =  $facade->renderTemplate();
            $view->elefundsCss = $facade->getPrintableCssTagStrings();
            $additionalCss = ConfigurationManager::getInternal('module/additionalCss/checkout');

            if (!empty($additionalCss)) {
                $view->elefundsAdditionalCss = $additionalCss;
                $view->elefundsHasAdditionalCss = TRUE;
            } else {
                $view->elefundsHasAdditionalCss = FALSE;
            }

            $view->elefundsJs = $facade->getPrintableJavascriptTagStrings();

            $view->extendsTemplate('checkout.tpl');
            $view->extendsTemplate($positionTemplate);
        }
    }

}