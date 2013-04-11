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

require_once dirname(__FILE__) . '/SDK/Facade.php';

/**
 * Bootstrap for the elefunds module.
 *
 * @package    elefunds Shopware Module
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <hello@elefunds.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 1.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var Elefunds_Facade
     */
    private $facade;

    /**
     * @var Shopware_Plugins_Frontend_LfndsDonation_Manager_DonationManager
     */
    private $donationManager;

    /**
     * Initializes the configuration form,  creates the database schema and subscribes
     * to checkout and document creation events.
     *
     * @return bool
     */
    public function install()
    {
        $this->createConfigurationForm();
        $this->createDatabaseSchema();
        $this->registerEvents();

        return TRUE;
    }

    /**
     * We do not need to process updates, so we just return TRUE.
     *
     * @param string $version
     * @return bool
     */
    public function update($version) {
        $this->invokeApiSync(TRUE);
        $this->registerEvents();
        $this->createConfigurationForm();

        return TRUE;
    }

    /**
     * Removes the elefunds footprint from the the system.
     *
     * @return bool
     */
    public function uninstall() {
        $this->invokeApiSync(TRUE);
        $this->registerCustomModels();
        $this->dropDatabaseSchema();
        return TRUE;
    }

    /**
     * Registers the custom models used in this plugin.
     *
     * @return void
     */
    public function afterInit()
    {
        $this->registerCustomModels();
    }

    /**
     * Prepares the donation if finish action is about to be
     * executed.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPreDispatchCheckout(Enlight_Controller_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();

        $action =  strtolower($request->getActionName());

        if ($action === 'payment' || $action === 'finish') {
            $this->prepareDonation($request);
            $this->prepareSession($request);
        }
    }

    /**
     * Injects the donation module if action is confirm and processes the donation
     * if everything is finished.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPostDispatchCheckout(Enlight_Controller_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();
        $request = $subject->Request();
        $view = $subject->View();

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
     * Cancels the donation when it's removed from an order in the backend.
     *
     * @param Enlight_Controller_EventArgs $args
     * @return void
     */
    public function onPositionRemovedFromOrder(Enlight_Controller_EventArgs $args) {
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
                if ($model instanceof \Shopware\Models\Order\Detail) {
                    if ($model->getArticleNumber() === 'ELEFUNDS-DONATION') {

                        /** @var Shopware\CustomModels\Elefunds\Donation\Donation $donation  */
                        $donation = $donationRepository->findOneBy(array('foreignId' => $order->getNumber()));
                        if ($donation !== NULL) {
                            $donationsToBeCancelled[] = $donation;
                        }
                    }
                }
            }
            $donationRepository->setStates($donationsToBeCancelled, \Shopware\CustomModels\Elefunds\Donation\Donation::SCHEDULED_FOR_CANCELLATION);
            $this->invokeApiSync(TRUE);
        }
    }

    /**
     * Adds the obligatory additional invoice text to a receipt with a donation.
     *
     * We do remove the donation from shipping notes.
     *
     * @param Enlight_Hook_HookArgs $args
     * @return void
     */
    public function onBeforeAssignValuesToDocument(Enlight_Hook_HookArgs $args) {

        $additionalInvoiceText = Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::getLocale('additionalInvoiceText');

        /** @var Shopware_Components_Document $document */
        $document = $args->getSubject();
        $positions = $document->_view->getTemplateVars('Pages');

        $documentType = $document->_view->getTemplateVars('Document');
        $isShippingNote = $documentType['template'] === Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::getInternal('documents/shippingTemplate');

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

    /**
     * Initiates the sync process that reports donation to the elefunds API and retrieves
     * a fresh set of receivers.
     *
     * @param bool $donationsOnly
     * @return void
     */
    public function invokeApiSync($donationsOnly = FALSE) {
        $syncManager = new Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager($this->getConfiguredFacade());
        $syncManager->syncDonations();

        if (!$donationsOnly) {
            $syncManager->syncReceivers();
        }

    }

    /**
     * Prepares the donationManager as a base point for calculating the donation changes and the donation.
     *
     * @param Enlight_Controller_Request_RequestHttp $request
     * @return void
     */
    protected function prepareDonation(Enlight_Controller_Request_RequestHttp $request) {
        $params = $request->getParams();

        if (isset($params['elefunds_agree']) && isset($params['elefunds_donation_cent']) && ctype_digit($params['elefunds_donation_cent'])) {

            /** +++ Input Validation +++ */
            $session = Shopware()->Session();

            $roundup  = (int)$params['elefunds_donation_cent'];

            // We have to cast the amount to string and then to int, as the session does not care about
            // floating point precision.
            $grandTotal = (int)(string)($session['sOrderVariables']['sAmount'] * 100);

            $receiverIds = array_map(function($x) { return (int)$x; }, explode(',', $params['elefunds_receivers']));

            if (isset($params['elefunds_suggested_round_up_cent']) && ctype_digit($params['elefunds_suggested_round_up_cent'])) {
                $suggestedRoundUp = (int)$params['elefunds_suggested_round_up_cent'];
            } else {
                $suggestedRoundUp = 0;
            }

            if (isset($params['elefunds_receipt_input'])) {
                $userData = $session['sOrderVariables']['sUserData'];
                $user = array(
                    'firstName'      =>  (string)$userData['billingaddress']['firstname'],
                    'lastName'       =>  (string)$userData['billingaddress']['lastname'],
                    'email'          =>  (string)$userData['additional']['user']['email'],
                    'streetAddress'  =>  (string)$userData['billingaddress']['street'] . ' ' . (string)$userData['billingaddress']['streetnumber'],
                    'zip'            =>  (string)$userData['billingaddress']['zipcode'],
                    'city'           =>  (string)$userData['billingaddress']['city']
                );

            } else {
                $user = array();
            }
            /** ^^^ Input Validation ^^^ */

            $this->donationManager = new Shopware_Plugins_Frontend_LfndsDonation_Manager_DonationManager($roundup, $receiverIds, $grandTotal, $suggestedRoundUp, $user);

        }
    }

    /**
     * Prepares the session parameters to extend the order.
     *
     * @param Enlight_Controller_Request_RequestHttp $request
     */
    protected function prepareSession(Enlight_Controller_Request_RequestHttp $request) {

        if ($this->donationManager !== NULL) {

            $basket = Shopware()->Session()->sOrderVariables['sBasket'];

            $basket['sAmount'] = $basket['sAmount'] + $this->donationManager->getRoundup(TRUE);
            $basket['content'][] = $this->donationManager->getMockedDonation();

            $amount = (float)str_replace(',', '.', $basket['Amount']) + $this->donationManager->getRoundup(TRUE);
            $basket['Amount'] = number_format($amount, 2, ',', '.');

            $amountNet = (float)str_replace(',', '.', $basket['AmountNet']) + $this->donationManager->getRoundup(TRUE);
            $basket['AmountNet'] = number_format($amountNet, 2, ',', '.');

            $basket['AmountNumeric'] = $basket['AmountNumeric'] + $this->donationManager->getRoundup(TRUE);
            $basket['AmountNetNumeric'] = $basket['AmountNetNumeric'] + $this->donationManager->getRoundup(TRUE);

            if(!empty($basket['AmountWithTax'])) {
                $amountWithTax = (float)str_replace(',', '.', $basket['AmountWithTax']) + $this->donationManager->getRoundup(TRUE);
                $basket['AmountWithTax'] = number_format($amountWithTax, 2, ',', '.');
            }

            if(!empty($basket['AmountWithTaxNumeric'])) {
                $basket['AmountWithTaxNumeric'] = $basket['AmountWithTaxNumeric'] + $this->donationManager->getRoundup(TRUE);
            }

            Shopware()->Session()->Elefunds = array(
                'receivers'          => $this->donationManager->getReceivers(),
                'amount'             => $basket['sAmount'],
                'roundUp'            => $this->donationManager->getRoundup(),
                'grandTotal'         => $this->donationManager->getGrandTotal(),
                'availableReceivers' => $this->donationManager->getAvailableReceiverIds(),
                'suggestedRoundUp'   => $this->donationManager->getSuggestedRoundUp(),
                'userData'           => $this->donationManager->getUserData(),
                'languageCode'       => $this->donationManager->getLanguageCode()
            );

            Shopware()->Session()->sOrderVariables['sBasket'] = $basket;
        }
    }

    /**
     * Calculates the donation, collects all necessary information and pushes them into the donation model.
     *
     * @param Enlight_View_Default $view
     *
     * @return void
     */
    protected function processDonation(Enlight_View_Default $view)
    {

        $session = Shopware()->Session();

        if (isset($session->Elefunds)) {
            $foreignId = $view->getAssign('sOrderNumber');

            /** @var Shopware\CustomModels\Elefunds\Donation\Repository $donationRepository  */
            $donationRepository = Shopware()->Models()->getRepository(
                'Shopware\CustomModels\Elefunds\Donation\Donation'
            );

            $donationRepository->addDonation(
                 $foreignId,
                 $session->Elefunds['roundUp'],
                 $session->Elefunds['grandTotal'],
                 array_keys($session->Elefunds['receivers']),
                 $session->Elefunds['availableReceivers'],
                 $session->Elefunds['userData'],
                 $session->Elefunds['languageCode'],
                 $session->Elefunds['suggestedRoundUp']
            );

            $this->Application()->Template()->addTemplateDir(
                $this->Path() . 'Views/', 'elefunds'
            );

            $facade = $this->getConfiguredFacade(TRUE);
            $includeFacebookShare = Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::getInternal('share/includeSocialMediaShare');

            if($includeFacebookShare) {
                $facade->getConfiguration()->getView()
                    ->assign('shopName', Shopware()->Config()->shopName)
                    ->assign('receivers', $session->Elefunds['receivers'])
                    ->assign('foreignId', $view->getAssign('sOrderNumber'));
            }

            $view->sAmount = $session->Elefunds['amount'];
            $view->elefundsCss = $includeFacebookShare ? $facade->getTemplateCssFiles() : array();
            $view->elefundsFacebookShare = $includeFacebookShare ? $facade->renderTemplate(dirname(__FILE__) . '/Views/CheckoutSuccess.phtml', TRUE) : '';

            $view->extendsTemplate('checkout_finish.tpl');
            $this->invokeApiSync();

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

        if (Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::isAcceptedPaymentProvider($paymentProviderId)) {
            $this->Application()->Template()->addTemplateDir(
                $this->Path() . 'Views/', 'elefunds'
            );

            $facade = $this->getConfiguredFacade();

            /** @var Shopware\CustomModels\Elefunds\Receiver\Repository $receiverRepository  */
            $receiverRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Elefunds\Receiver\Receiver');
            $receivers = $receiverRepository->getValidReceivers();

            if (count($receivers) < 3) {
                $syncManager = new Shopware_Plugins_Frontend_LfndsDonation_Manager_SyncManager($facade);
                $receivers = $syncManager->syncReceivers();
                if (count($receivers) < 3) {
                    // Okay, this line of code will hopefully never execute! We do ALWAYS provide three receivers.
                    return;
                }
            }


            $position = Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsPosition');
            $position = in_array($position, array('bottom', 'top')) ? $position : 'bottom';
            $positionTemplate = sprintf('checkout_%s.tpl', $position);
            $positionWidthKey = sprintf('module/widthInPixel/%s', ucfirst($position));

            $facade->getConfiguration()
                ->getView()
                ->assign('receivers', $receivers)
                ->assign('shopWidth', Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::getInternal($positionWidthKey))
                ->assign('currencyDelimiter', $facade->getConfiguration()->getCountrycode() === 'de' ? ',' : '.')
                ->assign('total', $view->sAmount * 100);

            try {
                $view->elefunds =  $facade->renderTemplate();
                $view->elefundsCss = $facade->getTemplateCssFiles();
                $view->elefundsJs = $facade->getTemplateJavascriptFiles();
                $view->extendsTemplate('checkout.tpl');
                $view->extendsTemplate($positionTemplate);

            } catch (Elefunds_Exception_ElefundsCommunicationException $error) {
                // Something went wrong. Hence, we do not display the plugin at  all.
            }
        }
    }

    /**
     * Summarizes all information about the elefunds module.
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version'       => $this->getVersion(),
            'label'         => 'elefunds Donation Module',
            'description'   => Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::loadFromFile('description.html'),
            'author'        => 'elefunds GmbH',
            'copyright'     => 'Copyright © 2013, elefunds GmbH',
            'support'       => 'https://elefunds.de',
            'link'          => 'https://elefunds.de'
        );
    }

    /**
     * Wrapper for the version string.
     *
     * @return string
     */
    public function getVersion() {
        return '1.1.0';
    }


    /**
     * Configures the facade based on the plugin settings and the current locale.
     *
     * @param bool $checkoutSuccess
     * @return Elefunds_Facade
     */
    protected  function getConfiguredFacade($checkoutSuccess = FALSE) {

        if ($this->facade === NULL) {

            if ($checkoutSuccess) {
                $configuration = new Shopware_Plugins_Frontend_LfndsDonation_Configuration_CheckoutSuccessConfiguration();
            } else {
                $configuration = new Shopware_Plugins_Frontend_LfndsDonation_Configuration_CheckoutConfiguration();
            }
            
            $configuration->setClientId(Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsClientId'))
                ->setApiKey(Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsApiKey'))
                ->setCountrycode(Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::getLanguage());
                
            $this->facade = new Elefunds_Facade($configuration);
            $this->facade->getConfiguration()
                ->getView()->assign('skin', array(
                                        'theme' =>  Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsTheme'),
                                        'color' =>  Shopware_Plugins_Frontend_LfndsDonation_Configuration_ConfigurationManager::get('elefundsColor')
                                    )
            );
            
        }

        return $this->facade;
    }

    /**
     * Creates the two needed tables:
     *
     *      * Receiver to cache the api receiver call
     *      * Donation to save made donation to be pushed to the API upon next sync
     *
     * @return void
     */
    protected function createDatabaseSchema() {

        $this->registerCustomModels();

        $modelManager = Shopware()->Models();
        $modelManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
        $schemaTool->createSchema(
            array(
                $modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Donation\Donation'),
                $modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Receiver\Receiver')
            )
        );
    }

    /**
     * Removes the entire elefunds footprint from the database.
     *
     * @return void
     */
    protected function dropDatabaseSchema() {

        $modelManager = Shopware()->Models();
        $modelManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $schemaTool = new Doctrine\ORM\Tools\SchemaTool($modelManager);
        $schemaTool->dropSchema(
            array(
                $modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Receiver\Receiver'),
                $modelManager->getClassMetadata('Shopware\CustomModels\Elefunds\Donation\Donation')
            )
        );
    }

    /**
     * Creates the configuration form for the plugin.
     *
     * Hence, it gives option to enter the clientId and the apiKey
     *
     * @return void
     */
    protected function createConfigurationForm() {
        $form = $this->Form();
        $form->setPluginId($this->getId());
        $form->setName('Elefunds');

        /** +++ API Credentials +++ */

        /** @var Shopware\Models\Config\Form $parentForm */
        $parentForm =  $this->Forms()->findOneBy(
            array(
                'name' => 'Other'
            )
        );
        $form->setParent($parentForm);

        $form->setElement(
            'number',
            'elefundsClientId',
            array(
                'label'     =>  'Client ID',
                'required'  =>  TRUE
            )
        );

        $form->setElement(
            'text',
            'elefundsApiKey',
            array(
                'label'     => 'API Key',
                'required'  => TRUE
            )
        );
        /** ^^^ API Credentials ^^^ */

        /** +++ Theming +++ */

        $form->setElement(
            'select',
            'elefundsPosition',
            array(
                'label' => 'Position',
                'store' => array(
                    array('top', 'oben'),
                    array('bottom', 'unten')
                ),
                'value' => 'bottom'
            )
        );

        $form->setElement(
            'select',
            'elefundsTheme',
            array(
                'label' => 'Schema',
                'store' => array(
                    array('light', 'hell'),
                    array('dark', 'dunkel')
                ),
                'value' => 'light'
            )
        );
        
        $form->setElement(
            'select',
            'elefundsColor',
            array(
                'label' => 'Farbe',
                'store' => array(
                    array('orange', 'orange'),
                    array('blue', 'blau'),
                    array('green', 'grün'),
                    array('purple', 'violet')
                ),
                'value' => 'orange'
            )
        );

        /** ^^^ Theming ^^^ */

        /** +++ Allowed payment options +++ */

        /** @var Shopware\Models\Payment\Repository $paymentRepository  */
        $paymentRepository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        $paymentProviders = $paymentRepository->findAll();

        foreach ($paymentProviders as $paymentProvider) {
            /** @var Shopware\Models\Payment\Payment $paymentProvider */
            $form->setElement(
                'boolean',
                'elefundsPaymentProvider' . $paymentProvider->getId(),
                array(
                    'label'  => sprintf(Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager::getLocale('acceptAsPaymentProvider'), $paymentProvider->getDescription()),
                    'value' => TRUE
                )
            );
        }

        /** ^^^ Allowed payment options ^^^ */

    }

    protected function registerEvents() {

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Frontend_Checkout',
            'onPreDispatchCheckout'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout',
            'onPostDispatchCheckout',
            0
        );

        $this->subscribeEvent(
            'Shopware_Components_Document::assignValues::after',
            'onBeforeAssignValuesToDocument'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PreDispatch_Backend_Order',
            'onPositionRemovedFromOrder'
        );
    }

}