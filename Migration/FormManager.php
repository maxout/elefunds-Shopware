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

use \Shopware_Plugins_Frontend_LfndsDonation_Locale_LocaleManager as LocaleManager;

/**
 * Takes care of the form processing.
 *
 * @package    elefunds Shopware Module
 * @subpackage Migration
 * @author     Christian Peters <christian@elefunds.de>
 * @copyright  2012 elefunds GmbH <contact@elefunds>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.elefunds.de
 * @since      File available since Release 2.0.0
 */
class Shopware_Plugins_Frontend_LfndsDonation_Migration_FormManager {

    /**
     * @var Shopware\Models\Config\Form
     */
    protected $form;

    /**
     * Initiates the Form Manager.
     *
     * @param int $pluginId
     * @param \Shopware\Models\Config\Form $form
     */
    public function __construct($pluginId, Shopware\Models\Config\Form $form) {
        $this->form = $form;
        $this->form->setPluginId($pluginId);
        $this->form->setName('Elefunds');
    }

    /**
     * Creates the configuration form for the plugin.
     *
     * Hence, it gives option to enter the clientId and the apiKey
     *
     * @return void
     */
    public function create() {



        /** +++ API Credentials +++ */

        /** @var Shopware\Models\Config\Form $parentForm */
        $parentForm =  Shopware()->Models()->getRepository('Shopware\Models\Config\Form')->findOneBy(
            array(
                'name' => 'Other'
            )
        );
        $this->form->setParent($parentForm);

        $this->form->setElement(
            'number',
            'elefundsClientId',
            array(
                'label'     =>  'Client ID',
                'required'  =>  TRUE
            )
        );

        $this->form->setElement(
            'text',
            'elefundsApiKey',
            array(
                'label'     => 'API Key',
                'required'  => TRUE
            )
        );
        /** ^^^ API Credentials ^^^ */

        /** +++ Theme +++ */

        $this->form->setElement(
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

        $this->form->setElement(
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


        $this->form->setElement(
            'color',
            'elefundsColor',
            array(
                'label' => 'Farbe',
                'value' => '#E1540F'
            )
        );

        /** ^^^ Theme ^^^ */

        /** +++ Allowed payment options +++ */

        /** @var Shopware\Models\Payment\Repository $paymentRepository  */
        $paymentRepository = Shopware()->Models()->getRepository('Shopware\Models\Payment\Payment');
        $paymentProviders = $paymentRepository->findAll();

        foreach ($paymentProviders as $paymentProvider) {
            /** @var Shopware\Models\Payment\Payment $paymentProvider */
            $this->form->setElement(
                'boolean',
                'elefundsPaymentProvider' . $paymentProvider->getId(),
                array(
                    'label'  => sprintf(LocaleManager::getLocale('acceptAsPaymentProvider'), $paymentProvider->getDescription()),
                    'value' => TRUE
                )
            );
        }

        /** ^^^ Allowed payment options ^^^ */

    }

}